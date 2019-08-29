<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use App\RegisteredMembers;
// use App\MembersRegisteredEvents;
// use App\EventRegistration;

use App\Event;
use App\Contact;
use App\ContactEvent;
use App\Notification;
use App\Setting;
use App\SmsPort;
use App\SentMessage;
use App\Fellowship;
use App\RegistrationKey;
use App\SmsRegisteredMembers;
use Monolog\Logger;
use Carbon\Carbon;

class ParseRegistrationMessage extends Controller
{
    protected $negarit_api_url;
    public function __construct() {
        $this->negarit_api_url = 'https://api.negarit.net/api/';
    }
    public function RegisterMembers() {
        $logger = new Logger("ActionTaskCtrl");
        // $request = request()->all();
        $request = request()->only('sms_port_id', 'sent_from', 'message');

        // $logger->log(Logger::INFO, "registration", [$request]);
    	// $request = request()->only('message', 'sent_from');
    	$message = $request['message'];
    	$sent_from = $request['sent_from'];
        $sms_port_id = $request['sms_port_id'];

        $split_message = explode(",", $message);

        $first_word = $split_message[0];

        $trim_first_word = trim($first_word);


        // find fellowship_id
        $contact_event = new ContactEvent();
        $notification = new Notification();
        $get_sms = SmsPort::where('negarit_sms_port_id', '=', $sms_port_id)->first();
        if($get_sms) {
            $get_sms_id = $get_sms->id;
            $fellowship_id = $get_sms->fellowship_id;
            $setting = Setting::where([['name', '=', 'API_KEY'], ['value', '=', $get_sms->api_key], ['fellowship_id', '=', $fellowship_id]])->first();
            $registration_key = RegistrationKey::where([['registration_key', '=', $trim_first_word], ['fellowship_id', '=', $fellowship_id]])->first();
            if($registration_key) {
                // $registration_key = json_decode($registration_key);
                $registration_key = json_decode($registration_key);
                // check whether registration end date is passed
                if((Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($registration_key->registration_end_date), false)) >= 0) {
                    $type = $registration_key->type;
                    $for_contact_update = $registration_key->for_contact_update;
                    if($type == 'event_registration') {
                        $event = Event::where([['event_name', '=', $registration_key->event], ['fellowship_id', '=', $fellowship_id]])->first();

                        if($event instanceof Event) {
                            
                            $contact = Contact::where([['phone', '=', $sent_from], ['fellowship_id', '=', $fellowship_id]])->first();
                            
                            if($contact != null) {
                                $is_registered = ContactEvent::where([['event_id', '=', $event->id],['contact_id', '=', $contact->id]])->first();
                                if($is_registered) {
                                    if(!$setting) {
                                        return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                                    }
                                    $duplication_message = 'Hello '. $contact->full_name.', you already registered for \''. $event->event_name.'\' event. Thank you';
                                    $sent_message_exists = SentMessage::where([['message', '=', $duplication_message], ['sent_to', '=', $contact->phone]])->exists();
                                    // return response()->json([$sent_message_exists], 200);
                                    if(!$sent_message_exists) {
                                        $sent_message = new SentMessage();
                                        $sent_message->message = $duplication_message;
                                        $sent_message->sent_to = $sent_from;
                                        $sent_message->is_sent = false;
                                        $sent_message->is_delivered = false;
                                        $sent_message->sms_port_id = $get_sms_id;
                                        $sent_message->fellowship_id = $fellowship_id;
                                        $sent_message->sent_by = $registration_key->created_by;

                                        if($sent_message->save()) {
                            
                                            $get_campaign_id = $get_sms->negarit_campaign_id;
                                            $get_message = $sent_message->message;
                                            $get_phone = $contact->phone;
                                            $get_sender = $sent_message->sent_by;

                                            // to send a post request (message) for Negarit API 
                                            $message_send_request = array();
                                            $message_send_request['API_KEY'] = $setting->value;
                                            $message_send_request['message'] = $get_message;
                                            $message_send_request['sent_to'] = $get_phone;
                                            $message_send_request['campaign_id'] = $get_campaign_id;
                                            
                                            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                                    'api_request/sent_message?API_KEY?='.$setting->value, 
                                                    json_encode($message_send_request));
                                            $decoded_response = json_decode($negarit_response);
                                            if($decoded_response) { 
                                                if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                                    $send_message = $decoded_response->sent_message;
                                                    $sent_message->is_sent = true;
                                                    $sent_message->is_delivered = true;
                                                    $sent_message->update();
                                                    $logger->log(Logger::INFO, "contact already registered for the event", [$event->event_name]);
                                                    return response()->json(['response' => 'contact already registered', 'message' => $decoded_response], 400);
                                                }
                                                return response()->json(['response' => $decoded_response], 500);
                                            }
                                            return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                                        }
                                    } else {
                                        return;
                                    }
                                }
                                $contact_event->event_id = $event->id;
                                $contact_event->contact_id = $contact->id;
                                $contact_event->through_sms = true;
                                $contact_event->save();

                                $registered_member = new SmsRegisteredMembers();
                                $registered_member->full_name = $contact->full_name;
                                $registered_member->phone = $contact->phone;
                                $registered_member->key = $registration_key->registration_key;
                                $registered_member->event = $registration_key->event;
                                $registered_member->registered_date = date('Y-m-d');
                                $registered_member->registration_end_date = $registration_key->registration_end_date;
                                $registered_member->fellowship_id = $registration_key->fellowship_id;
                                $registered_member->save();

                                if($registration_key->success_message_reply != null) {
                                    if(!$setting) {
                                        return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                                    }
                                    $sent_message = new SentMessage();
                                    $sent_message->message = $registration_key->success_message_reply;
                                    $sent_message->sent_to = $sent_from;
                                    $sent_message->is_sent = false;
                                    $sent_message->is_delivered = false;
                                    $sent_message->sms_port_id = $get_sms_id;
                                    $sent_message->fellowship_id = $fellowship_id;
                                    $sent_message->sent_by = $registration_key->created_by;

                                    if($sent_message->save()) {
                        
                                        $get_campaign_id = $get_sms->negarit_campaign_id;
                                        $get_message = $sent_message->message;
                                        $get_phone = $contact->phone;
                                        $get_sender = $sent_message->sent_by;

                                        // to send a post request (message) for Negarit API 
                                        $message_send_request = array();
                                        $message_send_request['API_KEY'] = $setting->value;
                                        $message_send_request['message'] = $get_message;
                                        $message_send_request['sent_to'] = $get_phone;
                                        $message_send_request['campaign_id'] = $get_campaign_id;
                                        
                                        $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                                'api_request/sent_message?API_KEY?='.$setting->value, 
                                                json_encode($message_send_request));
                                        $decoded_response = json_decode($negarit_response);
                                        if($decoded_response) { 
                                            if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                                $send_message = $decoded_response->sent_message;
                                                $sent_message->is_sent = true;
                                                $sent_message->is_delivered = true;
                                                $sent_message->update();
                                                $logger->log(Logger::INFO, "success message sent successfully for event registration", [$decoded_response]);
                                                return response()->json(['message' => 'success message reply sent successfully for registered member',
                                                'sent message' => $send_message], 200);
                                            }
                                            return response()->json(['response' => $decoded_response], 500);
                                        }
                                        return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                                    }
                                }
                            } else {
                                
                                $year = date('Y') + 4;

                                $user = $registration_key->created_by;
                                $user = json_decode($user);
                                
                                $new_contact = new Contact();
                                $new_contact->full_name = 'unknown';
                                $new_contact->gender = 'unknown';
                                $new_contact->phone = $sent_from;
                                $new_contact->email = null;
                                $new_contact->acadamic_department = 'unknown';
                                $new_contact->graduation_year = $year.'-07-30';
                                $new_contact->is_under_graduate = true;
                                $new_contact->is_this_year_gc = false;
                                $new_contact->fellowship_id = $fellowship_id;
                                $new_contact->created_by = $user->full_name;
                                $new_contact->save();

                                $contact_event->event_id = $event->id;
                                $contact_event->contact_id = $new_contact->id;
                                $contact_event->through_sms = true;
                                $contact_event->save();

                                $registered_member = new SmsRegisteredMembers();
                                $registered_member->full_name = $new_contact->full_name;
                                $registered_member->phone = $new_contact->phone;
                                $registered_member->key = $registration_key->registration_key;
                                $registered_member->event = $registration_key->event;
                                $registered_member->registered_date = date('Y-m-d');
                                $registered_member->registration_end_date = $registration_key->registration_end_date;
                                $registered_member->fellowship_id = $registration_key->fellowship_id;
                                $registered_member->save();
                                
                                if($registration_key->success_message_reply != null) {
                                    if(!$setting) {
                                        return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                                    }
                                    $sent_message = new SentMessage();
                                    $sent_message->message = $registration_key->success_message_reply;
                                    $sent_message->sent_to = $new_contact->phone;
                                    $sent_message->is_sent = false;
                                    $sent_message->is_delivered = false;
                                    $sent_message->sms_port_id = $get_sms_id;
                                    $sent_message->fellowship_id = $fellowship_id;
                                    $sent_message->sent_by = $registration_key->created_by;
                                    
                                    if($sent_message->save()) {
                                        $get_campaign_id = $get_sms->negarit_campaign_id;
                                        $get_message = $sent_message->message;
                                        $get_phone = $sent_message->sent_to;
                                        $get_sender = $sent_message->sent_by;

                                        // to send a post request (message) for Negarit API 

                                        $message_send_request = array();
                                        $message_send_request['API_KEY'] = $setting->value;
                                        $message_send_request['message'] = $get_message;
                                        $message_send_request['sent_to'] = $get_phone;
                                        $message_send_request['campaign_id'] = $get_campaign_id;
                                        $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                                'api_request/sent_message?API_KEY?='.$setting->value, 
                                                json_encode($message_send_request));
                                        $decoded_response = json_decode($negarit_response);
                                        if($decoded_response) { 
                                            if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                                $send_message = $decoded_response->sent_message;
                                                $sent_message->is_sent = true;
                                                $sent_message->is_delivered = true;
                                                $sent_message->update();
                                                $logger->log(Logger::INFO, "success message sent successfully", [$decoded_response]);
                                                return response()->json(['message' => 'success message reply sent successfully for registered member',
                                                'sent message' => $send_message], 200);
                                            }
                                            return response()->json(['response' => $decoded_response], 500);
                                        }
                                        return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                                    }
                                }
                                
                            }
                        }
                        
                    } else if($for_contact_update == 1) {
                        
                        if(count($split_message) < 3) {
                            if(!$setting) {
                                return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                            }
                            $sent_message = new SentMessage();
                            $sent_message->message = 'Invalid request! full name and graduation year must be provided by separation with comma';
                            $sent_message->sent_to = $sent_from;
                            $sent_message->is_sent = false;
                            $sent_message->is_delivered = false;
                            $sent_message->sms_port_id = $get_sms_id;
                            $sent_message->fellowship_id = $fellowship_id;
                            $sent_message->sent_by = $registration_key->created_by;
                            if($sent_message->save()) {
                                $get_campaign_id = $get_sms->negarit_campaign_id;
                                $get_message = $sent_message->message;
                                $get_phone = $sent_from;
                                $get_sender = $sent_message->sent_by;

                                // to send a post request (message) for Negarit API 
                                $message_send_request = array();
                                $message_send_request['API_KEY'] = $setting->value;
                                $message_send_request['message'] = $get_message;
                                $message_send_request['sent_to'] = $get_phone;
                                $message_send_request['campaign_id'] = $get_campaign_id;

                                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                        'api_request/sent_message?API_KEY?='.$setting->value, 
                                        json_encode($message_send_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                        $send_message = $decoded_response->sent_message;
                                        $sent_message->is_sent = true;
                                        $sent_message->is_delivered = true;
                                        $sent_message->update();
                                        $logger->log(Logger::INFO, "contact updated successfully", []);
                                        return response()->json(['message' => 'message sent successfully',
                                        'sent message' => $send_message], 200);
                                    }
                                    return response()->json(['response' => $decoded_response], 500);
                                }
                                return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                            }
                        }
                        $gra_year = trim($split_message[2]);
                        if(!(ctype_digit(trim($split_message[2]))) || strlen($gra_year) < 4 || strlen($gra_year) > 4) {
                            if(!$setting) {
                                return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                            }
                            $next_year = date('Y') + 1;
                            $sent_message = new SentMessage();
                            $sent_message->message = 'Invalid request! graduation year '. $gra_year. ' is not valid, it should be like '. $next_year;
                            $sent_message->sent_to = $sent_from;
                            $sent_message->is_sent = false;
                            $sent_message->is_delivered = false;
                            $sent_message->sms_port_id = $get_sms_id;
                            $sent_message->fellowship_id = $fellowship_id;
                            $sent_message->sent_by = $registration_key->created_by;
                            if($sent_message->save()) {
                                $get_campaign_id = $get_sms->negarit_campaign_id;
                                $get_message = $sent_message->message;
                                $get_phone = $sent_from;
                                $get_sender = $sent_message->sent_by;

                                // to send a post request (message) for Negarit API 
                                $message_send_request = array();
                                $message_send_request['API_KEY'] = $setting->value;
                                $message_send_request['message'] = $get_message;
                                $message_send_request['sent_to'] = $get_phone;
                                $message_send_request['campaign_id'] = $get_campaign_id;

                                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                        'api_request/sent_message?API_KEY?='.$setting->value, 
                                        json_encode($message_send_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                        $send_message = $decoded_response->sent_message;
                                        $sent_message->is_sent = true;
                                        $sent_message->is_delivered = true;
                                        $sent_message->update();
                                        $logger->log(Logger::INFO, "contact updated successfully", []);
                                        return response()->json(['message' => 'message sent successfully',
                                        'sent message' => $send_message], 200);
                                    }
                                    return response()->json(['response' => $decoded_response], 500);
                                }
                                return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                            }
                        }
                        $full_name = trim($split_message[1]);
                        $phone = $sent_from;
                        $graduation_year = trim($split_message[2]).'-07-30';
                        
                        $this_year_gc = false;
                        $is_under_graduate = true;
                        
                        $parse_graduation_year = Carbon::parse($graduation_year);
                        
                        $today = Carbon::parse(date('Y-m-d'));
                        $difference = $today->diffInDays($parse_graduation_year, false);
                        if($difference <= 0) {
                            $is_under_graduate = false;
                        } else if($difference < 380 && $difference > 0) {
                            $is_under_graduate = true;
                            $this_year_gc = true;
                        }

                        $contact = Contact::where([['phone', '=', $sent_from], ['fellowship_id', '=', $fellowship_id]])->first();

                        if($contact != null) {
                            $contact->full_name = $full_name;
                            $contact->gender = $contact->gender;
                            $contact->phone = $phone;
                            $contact->email = $contact->email;
                            $contact->acadamic_department = $contact->acadamic_department;
                            $contact->graduation_year = $graduation_year;
                            $contact->is_under_graduate = $is_under_graduate;
                            $contact->is_this_year_gc = $this_year_gc;
                            $contact->fellowship_id = $fellowship_id;
                            if($contact->update()) {
                                $registered_member = new SmsRegisteredMembers();
                                $registered_member->full_name = $contact->full_name;
                                $registered_member->phone = $contact->phone;
                                $registered_member->key = $registration_key->registration_key;
                                $registered_member->event = 'contact update';
                                $registered_member->registered_date = date('Y-m-d');
                                $registered_member->registration_end_date = $registration_key->registration_end_date;
                                $registered_member->fellowship_id = $registration_key->fellowship_id;
                                $registered_member->save();

                                if($registration_key->success_message_reply != null) {
                                    if(!$setting) {
                                        return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                                    }
                                    $sent_message = new SentMessage();
                                    $sent_message->message = $registration_key->success_message_reply;
                                    $sent_message->sent_to = $sent_from;
                                    $sent_message->is_sent = false;
                                    $sent_message->is_delivered = false;
                                    $sent_message->sms_port_id = $get_sms_id;
                                    $sent_message->fellowship_id = $fellowship_id;
                                    $sent_message->sent_by = $registration_key->created_by;

                                    if($sent_message->save()) {

                                        $get_campaign_id = $get_sms->negarit_campaign_id;
                                        $get_message = $sent_message->message;
                                        $get_phone = $sent_from;
                                        $get_sender = $sent_message->sent_by;

                                        // to send a post request (message) for Negarit API 
                                        $message_send_request = array();
                                        $message_send_request['API_KEY'] = $setting->value;
                                        $message_send_request['message'] = $get_message;
                                        $message_send_request['sent_to'] = $get_phone;
                                        $message_send_request['campaign_id'] = $get_campaign_id;
                                        
                                        $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                                'api_request/sent_message?API_KEY?='.$setting->value, 
                                                json_encode($message_send_request));
                                        $decoded_response = json_decode($negarit_response);
                                        if($decoded_response) { 
                                            if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                                $send_message = $decoded_response->sent_message;
                                                $sent_message->is_sent = true;
                                                $sent_message->is_delivered = true;
                                                $sent_message->update();
                                                $logger->log(Logger::INFO, "contact updated successfully", []);
                                                return response()->json(['message' => 'message sent successfully',
                                                'sent message' => $send_message], 200);
                                            }
                                            return response()->json(['response' => $decoded_response], 500);
                                        }
                                        return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                                    }
                                }
                            }
                        } else {
                            $user = $registration_key->created_by;
                            $user = json_decode($user);
                            $contact = new Contact();
                            $contact->full_name = $full_name;
                            $contact->gender = 'unknown';
                            $contact->phone = $phone;
                            $contact->email = null;
                            $contact->Acadamic_department = 'unknown';
                            $contact->graduation_year = $graduation_year;
                            $contact->is_under_graduate = $is_under_graduate;
                            $contact->is_this_year_gc = $this_year_gc;
                            $contact->fellowship_id = $fellowship_id;
                            $contact->created_by = $user->full_name;
                            if($contact->save()) {
                                $registered_member = new SmsRegisteredMembers();
                                $registered_member->full_name = $contact->full_name;
                                $registered_member->phone = $contact->phone;
                                $registered_member->key = $registration_key->registration_key;
                                $registered_member->event = 'contact update';
                                $registered_member->registered_date = date('Y-m-d');
                                $registered_member->registration_end_date = $registration_key->registration_end_date;
                                $registered_member->fellowship_id = $registration_key->fellowship_id;
                                $registered_member->save();

                                if($registration_key->success_message_reply != null) {
                                    if(!$setting) {
                                        return response()->json(['error' => 'API key is not found, to send a success message for registered message'], 404);
                                    }
                                    $sent_message = new SentMessage();
                                    $sent_message->message = $registration_key->success_message_reply;
                                    $sent_message->sent_to = $sent_from;
                                    $sent_message->is_sent = false;
                                    $sent_message->is_delivered = false;
                                    $sent_message->sms_port_id = $get_sms_id;
                                    $sent_message->fellowship_id = $fellowship_id;
                                    $sent_message->sent_by = $registration_key->created_by;

                                    if($sent_message->save()) {
                                        $get_campaign_id = $get_sms->negarit_campaign_id;
                                        $get_message = $sent_message->message;
                                        $get_phone = $sent_from;
                                        $get_sender = $sent_message->sent_by;

                                        // to send a post request (message) for Negarit API 
                                        $message_send_request = array();
                                        $message_send_request['API_KEY'] = $setting->value;
                                        $message_send_request['message'] = $get_message;
                                        $message_send_request['sent_to'] = $get_phone;
                                        $message_send_request['campaign_id'] = $get_campaign_id;
                                        
                                        $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                                                'api_request/sent_message?API_KEY?='.$setting->value, 
                                                json_encode($message_send_request));
                                        $decoded_response = json_decode($negarit_response);
                                        if($decoded_response) { 
                                            if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                                $send_message = $decoded_response->sent_message;
                                                $sent_message->is_sent = true;
                                                $sent_message->is_delivered = true;
                                                $sent_message->update();
                                                $logger->log(Logger::INFO, "contact registered successfully", []);
                                                return response()->json(['message' => 'message sent successfully',
                                                'sent message' => $send_message], 200);
                                            }
                                            return response()->json(['response' => $decoded_response], 500);
                                        }
                                        return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                                    }
                                }

                            }
                        }
                    }
                }
            }
        }
    }
    public function getRegisteredMembers() {

    }
    public function deleteRegisteredMember($id) {

    }
}
