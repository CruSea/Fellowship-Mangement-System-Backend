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
use Monolog\Logger;

class ParseRegistrationMessage extends Controller
{
    protected $negarit_api_url;
    public function __construct() {
        $this->negarit_api_url = 'http://api.negarit.net/api/';
    }
    public function RegisterMembers() {
    	// $request = request()->all();
    	$request = request()->only('message', 'sent_from');
    	$message = $request['message'];
    	$sent_from = $request['sent_from'];
    	$split_message = explode(",", $message);
    	$count_splited_message = count($split_message);

    	if($count_splited_message < 2 || $count_splited_message > 2) {
            exit();
    		// return response()->json(['''user is not registered successfully'], 400);
    	}
    	$reg = $split_message[0];

    	$reg_trim = trim($reg);

    	$event = $split_message[1];
    	$event_trim = trim($event);
    	$logger = new Logger("ActionTaskCtrl");

    	if(strtolower($reg_trim) != "reg") {
    		$logger->log(Logger::INFO, "registration formate is not right", [$event_trim]);
    		// return response()->json(['error' => 'the format is not right'], 400);
    	}
        $event = Event::where('event_name', '=', $event_trim)->first();
        if(!$event) {
            exit();
        }

    	
    	// $logger->log(Logger::INFO, "NEGARIT_LOG", [$event_trim]);
        $lastMessage = SentMessage::latest()->first();
        $user = $lastMessage->sent_by;
        $setting = Setting::where('name', '=', 'API_KEY')->first();
        $sms_port = SmsPort::latest()->first();
        $sms_port_id = $sms_port->id;

        $notification = new Notification();
    	$event_id = $event->id;
    	$contact_event = new ContactEvent();
        $sent_from0 = Str::replaceArray("+251", ['0'], $sent_from);
        $contact0 = Contact::where('phone', '=', $sent_from0)->first();
        if($contact0 instanceof Contact) {
            $is_registered = ContactEvent::where([['event_id', '=', $event_id],['contact_id', '=', $contact0->id]])->first();
            if($is_registered) {
                $logger->log(Logger::INFO, "contact already registered for the event", [$event_trim]);
                return response()->json(['message' => 'contact already registered'], 400);
            }
            $contact_event->event_id = $event_id;
            $contact_event->contact_id = $contact0->id;
            if($contact_event->save()) {
                $notification->notification = $contact0->full_name.' registered for '. $event_trim.' through sms';
                $notification->save();
                // $logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
                // return response()->json(['message' => 'user registered for '.$event_trim . ' event successfully'], 200);
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $sentMessage = new SentMessage([
                    'message' => "successfully registered for ".$event_trim,
                    'sent_to' => $contact0->phone,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $sms_port_id,
                    'sent_by' => $user,
                ]);
                if($sentMessage->save()) {
                    
                    $get_campaign_id = $sms_port->negarit_campaign_id;
                    $get_api_key = $sms_port->negarit_sms_port_id;
                    $get_message = $sentMessage->message;
                    $get_phone = $sentMessage->sent_to;
                    $get_sender = $sentMessage->sent_by;

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
                            $sentMessage->is_sent = true;
                            $sentMessage->is_delivered = true;
                            $sentMessage->update();
                            return response()->json(['message' => 'message sent successfully',
                            'sent message' => $send_message], 200);
                        }
                        return response()->json(['response' => $decoded_response], 500);
                    }
                    return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                }
                
            }
        }
        $contact = Contact::where('phone', '=', $sent_from)->first();
        if($contact instanceof Contact) {
            $is_registered = ContactEvent::where([['event_id', '=', $event_id],['contact_id', '=', $contact->id]])->first();
            if($is_registered) {
                $logger->log(Logger::INFO, "contact already registered for the event", [$event_trim]);
                return response()->json(['message' => 'contact already registered'], 400);
            }
            $contact_event->event_id = $event_id;
            $contact_event->contact_id = $contact->id;
            if($contact_event->save()) {
                $notification->notification = $contact->full_name.' registered for '. $event_trim.' through sms';
                // $logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
                // return response()->json(['message' => 'user registered for '.$event_trim . ' event successfully'], 200);
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $sentMessage = new SentMessage([
                    'message' => "successfully registered for ".$event_trim,
                    'sent_to' => $contact->phone,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $sms_port_id,
                    'sent_by' => $user,
                ]);
                if($sentMessage->save()) {
                    
                    $get_campaign_id = $sms_port->negarit_campaign_id;
                    $get_api_key = $sms_port->negarit_sms_port_id;
                    $get_message = $sentMessage->message;
                    $get_phone = $sentMessage->sent_to;
                    $get_sender = $sentMessage->sent_by;

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
                            $sentMessage->is_sent = true;
                            $sentMessage->is_delivered = true;
                            $sentMessage->update();
                            return response()->json(['message' => 'message sent successfully',
                            'sent message' => $send_message], 200);
                        }
                        return response()->json(['response' => $decoded_response], 500);
                    }
                    return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                }
            }
        }
        $sent_from9 = Str::replaceArray('+2519', ['9'], $sent_from);
        $contact9 = Contact::where('phone', '=', $sent_from9)->first();
        if($contact9 instanceof Contact) {
            $is_registered = ContactEvent::where([['event_id', '=', $event_id],['contact_id', '=', $contact9->id]])->first();
            if($is_registered) {
                $logger->log(Logger::INFO, "contact already registered for the event", [$event_trim]);
                return response()->json(['message' => 'contact already registered'], 400);
            }
            $contact_event->event_id = $event_id;
            $contact_event->contact_id = $contact9->id;
            if($contact_event->save()) {
                $notification->notification = $contact9->full_name.' registered for '. $event_trim.' through sms';
                // $logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
                // return response()->json(['message' => 'user registered for '.$event_trim . ' event successfully'], 200);
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $sentMessage = new SentMessage([
                    'message' => "successfully registered for ".$event_trim,
                    'sent_to' => $contact9->phone,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $sms_port_id,
                    'sent_by' => $user,
                ]);
                if($sentMessage->save()) {
                    
                    $get_campaign_id = $sms_port->negarit_campaign_id;
                    $get_api_key = $sms_port->negarit_sms_port_id;
                    $get_message = $sentMessage->message;
                    $get_phone = $sentMessage->sent_to;
                    $get_sender = $sentMessage->sent_by;

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
                            $sentMessage->is_sent = true;
                            $sentMessage->is_delivered = true;
                            $sentMessage->update();
                            return response()->json(['message' => 'message sent successfully',
                            'sent message' => $send_message], 200);
                        }
                        return response()->json(['response' => $decoded_response], 500);
                    }
                    return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                }
            }
        }
        $sent_from251 = Str::replaceArray('+251', ['251'], $sent_from);
        $contact251 = Contact::where('phone', '=', $sent_from251)->first();
        if($contact251 instanceof Contact) {
            $is_registered = ContactEvent::where([['event_id', '=', $event_id],['contact_id', '=', $contact251->id]])->first();
            if($is_registered) {
                $logger->log(Logger::INFO, "contact already registered for the event", [$event_trim]);
                return response()->json(['message' => 'contact already registered'], 400);
            }
            $contact_event->event_id = $event_id;
            $contact_event->contact_id = $contact251->id;
            if($contact_event->save()) {
                $notification->notification = $contact251->full_name.' registered for '. $event_trim.' through sms';
                // $logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
                // return response()->json(['message' => 'user registered for '.$event_trim->event_name . ' event successfully'], 200);
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $sentMessage = new SentMessage([
                    'message' => "successfully registered for ".$event_trim,
                    'sent_to' => $contact251->phone,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $sms_port_id,
                    'sent_by' => $user,
                ]);
                if($sentMessage->save()) {
                    
                    $get_campaign_id = $sms_port->negarit_campaign_id;
                    $get_api_key = $sms_port->negarit_sms_port_id;
                    $get_message = $sentMessage->message;
                    $get_phone = $sentMessage->sent_to;
                    $get_sender = $sentMessage->sent_by;

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
                            $sentMessage->is_sent = true;
                            $sentMessage->is_delivered = true;
                            $sentMessage->update();
                            return response()->json(['message' => 'message sent successfully',
                            'sent message' => $send_message], 200);
                        }
                        return response()->json(['response' => $decoded_response], 500);
                    }
                    return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
                }
            }
        }
        $logger->log(Logger::INFO, "only fellowship members can register for events", []);
        return response()->json(['error' => 'contact is not the member of fellowship', 'message' => 'only fellowship members can register for events'], 404);



        // check user registered before
    	// $is_user_registered = RegisteredMembers::where('phone', '=', $sent_from)->first();
    	// if($is_user_registered) {
    	// 	$logger->log(Logger::INFO, "phone is already found", [$event_trim]);
    	// 	return response()->json(['message' => 'user registered before'], 403);
    	// }
    	// $registered_member = new RegisteredMembers();
    	// $registered_member->phone = $sent_from;
    	// $event_registration = EventRegistration::where('event_registration_title', '=', $event_trim)->first();
    	// $members_registered_events = new MembersRegisteredEvents();
    	// if($event_registration instanceof EventRegistration) {
    	// 	if($registered_member->save()) {
	    // 		$registered_member_id = $registered_member->id;
	    // 		$event_registration_id = $event_registration->id;
	    // 		$members_registered_events->event_registration_id = $event_registration_id;
	    // 		$members_registered_events->registered_member_id = $registered_member_id;
	    // 		if($members_registered_events->save()) {
	    // 			$logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
	    // 			return response()->json(['message' => 'user registered for '.$event_registration->event_registration_title . ' event successfully'], 200);
	    // 		}
	    // 		$registered_member->delete();
	    // 		$logger->log(Logger::INFO, "Ooops! something went wrong", [$event_trim]);
	    // 		return response()->json(['error' => 'Ooops! something went wrong', 'error' => 'user is not regstered for '. $event_registration->event_registration_title.' event'], 500);
	    // 	}
    	// } else {
    	// 	$logger->log(Logger::INFO, "event registratoin is not found", [$event_trim]);
    	// 	return response()->json(['error' => 'event registration is not found and the event title is '. $registered_member->phone], 404);
    	// }
    }
    public function getRegisteredMembers() {

    }
    public function deleteRegisteredMember($id) {

    }
}
