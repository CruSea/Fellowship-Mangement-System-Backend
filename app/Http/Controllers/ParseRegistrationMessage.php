<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RegisteredMembers;
use App\MembersRegisteredEvents;
use App\EventRegistration;
use Monolog\Logger;

class ParseRegistrationMessage extends Controller
{
    public function RegisterMembers() {
    	// $request = request()->all();
    	$request = request()->only('message', 'sent_from');
    	$message = $request['message'];
    	$sent_from = $request['sent_from'];
    	$split_message = explode(",", $message);
    	$count_splited_message = count($split_message);
    	if($count_splited_message < 2 || $count_splited_message > 2) {
    		// return response()->json(['''user is not registered successfully'], 400);
    	}
    	$reg = $split_message[0];

    	$reg_trim = trim($reg);

    	$event = $split_message[1];
    	$event_trim = trim($event);
    	
    	if(strtolower($reg_trim) != "reg") {
    		$logger->log(Logger::INFO, "registration formate is not right", [$event_trim]);
    		// return response()->json(['error' => 'the format is not right'], 400);
    	}


    	$logger = new Logger("ActionTaskCtrl");
    	// $logger->log(Logger::INFO, "NEGARIT_LOG", [$event_trim]);
    	
    	// check user registered before
    	$is_user_registered = RegisteredMembers::where('phone', '=', $sent_from)->first();
    	if($is_user_registered) {
    		$logger->log(Logger::INFO, "phone is already found", [$event_trim]);
    		return response()->json(['message' => 'user registered before'], 403);
    	}
    	$registered_member = new RegisteredMembers();
    	$registered_member->phone = $sent_from;
    	$event_registration = EventRegistration::where('event_registration_title', '=', $event_trim)->first();
    	$members_registered_events = new MembersRegisteredEvents();
    	if($event_registration instanceof EventRegistration) {
    		if($registered_member->save()) {
	    		$registered_member_id = $registered_member->id;
	    		$event_registration_id = $event_registration->id;
	    		$members_registered_events->event_registration_id = $event_registration_id;
	    		$members_registered_events->registered_member_id = $registered_member_id;
	    		if($members_registered_events->save()) {
	    			$logger->log(Logger::INFO, "user registed successfully", [$event_trim]);
	    			return response()->json(['message' => 'user registered for '.$event_registration->event_registration_title . ' event successfully'], 200);
	    		}
	    		$registered_member->delete();
	    		$logger->log(Logger::INFO, "Ooops! something went wrong", [$event_trim]);
	    		return response()->json(['error' => 'Ooops! something went wrong', 'error' => 'user is not regstered for '. $event_registration->event_registration_title.' event'], 500);
	    	}
    	} else {
    		$logger->log(Logger::INFO, "event registratoin is not found", [$event_trim]);
    		return response()->json(['error' => 'event registration is not found and the event title is '. $registered_member->phone], 404);
    	}
    }
    public function getRegisteredMembers() {

    }
    public function deleteRegisteredMember($id) {

    }
}
