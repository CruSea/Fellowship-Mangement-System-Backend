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
                $notification->save();
                // $logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
                // return response()->json(['message' => 'user registered for '.$event_trim . ' event successfully'], 200);
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                
                $sent_message = new SentMessage();
                $sent_message->message = "successfully registered for ".$event_trim;
                $sent_message->sent_to = $contact->phone;
                $sent_message->is_sent = false;
                $sent_message->is_delivered = false;
                $sent_message->sms_port_id = $sms_port_id;
                $sent_message->fellowship_id = $user->fellowship_id;
                $sent_message->sent_by = $user;
                if($sent_message->save()) {
                    
                    $get_campaign_id = $sms_port->negarit_campaign_id;
                    $get_api_key = $sms_port->negarit_sms_port_id;
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
    }
    public function getRegisteredMembers() {

    }
    public function deleteRegisteredMember($id) {

    }
}
