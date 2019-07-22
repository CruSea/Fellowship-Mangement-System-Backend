<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\EventRegistration;
use App\User;
use App\Team;
use App\Fellowship;
use App\Contact;
use App\ContactTeam;
use App\Setting;
use App\SentMessage;
use App\SmsPort;
use App\Event;
use App\ContactEvent;
use JWTAuth;

class EventRegistrationController extends Controller
{
	protected $negarit_api_url;
	public function __construct() {
		$this->negarit_api_url = 'http://api.negarit.net/api/';
	}
    public function SendRegistrationFormForTeam() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_registration_title', 'port_name', 'team', 'message');
    			$rule = [
    				'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'port_name' => 'required|string|min:1',
    				'team' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$team = Team::where('name', '=', $request['team'])->first();
    			if($team instanceof Team) {
    				$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    				if($sms_port instanceof SmsPort) {

    					$team_id = $team->id;
		    			$sms_port_id = $sms_port->id;

    					$event_registration = new EventRegistration();
		    			$event_registration->event_registration_title = $request['event_registration_title'];
		    			$event_registration->team_id = $team_id;
		    			$event_registration->message = $request['message'];
		    			$event_registration->sent_by = $user;
		    			$event_registration->save();

		    			

		    			$contacts = Contact::whereIn('id', ContactTeam::where('team_id', '=', $team_id)->select('contact_id')->get())->get();

		    			$setting = Setting::where('name', '=', 'API_KEY')->first();
		    			if(!$setting) {
		    				return response()->json(['error' => 'Api Key is not found'], 404);
		    			}
		    			for($i = 0; $i < count($contacts); $i++) {
		    				$contact = $contacts[$i];
		    				$sent_message = new SentMessage([
		    					'message' => $request['message'],
		    					'sent_to' => $contact->phone,
		    					'is_sent' => false,
		    					'is_delivered' => false,
		    					'sms_port_id' => $sms_port_id,
		    					'sent_by' => $user,
		    				]);
		    				if(!$sent_message->save()) {
		    					$sent_message_again = new SentMessage();
	                			$sent_message_again->message = $request['message'];
	                			$sent_message_again->sent_to = $contact->phone;
	                			$sent_message_again->is_sent = false;
	                			$sent_message_again->is_delivered = false;
	                			$sent_message_again->sms_port_id = $sms_port_id;
	                			$sent_message_again->sent_by = $user;
		                		$sent_message_again->save();
		    					// return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);

		    				}
	    					$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
		    			}
		    			$negarit_message_request = array();
			            $negarit_message_request['API_KEY'] = $setting->value;
			            $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
			            $negarit_message_request['messages'] = $insert;

			            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 'api_request/sent_multiple_messages',json_encode($negarit_message_request));
			            $decoded_response = json_decode($negarit_response);
			            if($decoded_response) {
			            	if(isset($decoded_response->satus)) {
			            		$sent_message->is_sent = true;
			            		$sent_message->is_delivered = true;
			            		return response()->json(['message' => $decoded_response], 200);
			            	} else {
				            	return response()->json(['message' => $decoded_response], 500);
				            }
			            } else {
				            return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
				        }
    				} else {
						return response()->json(['error' => 'sms port is not found'], 404);
					}
    				
    			} else {
    				return response()->json(['error' => 'team is not found'], 404);
    			}
    			
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['messag' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function sendRegistrationFormForFellowship() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_registration_title', 'port_name', 'message');
    			$rule = [
    				'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'port_name' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if($sms_port instanceof SmsPort) {
    				$fellowship_id = $user->fellowship_id;
    				$sms_port_id = $sms_port->id;

    				$event_registration = new EventRegistration();
	    			$event_registration->event_registration_title = $request['event_registration_title'];
	    			$event_registration->fellowship_id = $fellowship_id;
	    			$event_registration->message = $request['message'];
	    			$event_registration->sent_by = $user;
	    			$event_registration->save();

	    			$contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

	    			$setting = Setting::where('name', '=', 'API_KEY')->first();
	                if(!$setting) {
	                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
	                }

	                for($i = 0; $i < count($contacts); $i++) {
	                	
	                	$contact = $contacts[$i];
	                	if($contact->is_under_graduate) {
	                		$sent_message = new SentMessage([
	                			'message' => $request['message'],
	                			'sent_to' => $contact->phone,
	                			'is_sent' => false,
	                			'is_delivered' => false,
	                			'sms_port_id' => $sms_port_id,
	                			'sent_by' => $user,
	                		]);
	                		if(!$sent_message->save()) {
	                			$sent_message_again = new SentMessage();
	                			$sent_message_again->message = $request['message'];
	                			$sent_message_again->sent_to = $contact->phone;
	                			$sent_message_again->is_sent = false;
	                			$sent_message_again->is_delivered = false;
	                			$sent_message_again->sms_port_id = $sms_port_id;
	                			$sent_message_again->sent_by = $user;
		                		$sent_message_again->save();
	                		}

	                		$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
	                	}
	                }
	                $negarit_message_request = array();
		            $negarit_message_request['API_KEY'] = $setting->value;
		            $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
		            $negarit_message_request['messages'] = $insert;

		            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 'api_request/sent_multiple_messages',json_encode($negarit_message_request));
		            $decoded_response = json_decode($negarit_response);
		            if($decoded_response) {
		            	if(isset($decoded_response->satus)) {
		            		$sent_message->is_sent = true;
		            		$sent_message->is_delivered = true;
		            		return response()->json(['message' => $decoded_response], 200);
		            	} else {
			            	return response()->json(['message' => $decoded_response], 500);
			            }
		            } else {
			            return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			        }
    			} else {
    				return response()->json(['error' => 'sms port is not found'], 404);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['messag' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function sendRegistrationFormForEvent() {
    	try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $request = request()->only('event_registration_title', 'port_name', 'event', 'message');
    			$rule = [
    				'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'port_name' => 'required|string|min:1',
    				'event' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$event = Event::where('event_name', '=', $request['event'])->first();
    			if($event instanceof Event) {
    				$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    				if($sms_port instanceof SmsPort) {
    					$event_id = $event->id;
		    			$sms_port_id = $sms_port->id;

    					$event_registration = new EventRegistration();
		    			$event_registration->event_registration_title = $request['event_registration_title'];
		    			$event_registration->event_id = $event_id;
		    			$event_registration->message = $request['message'];
		    			$event_registration->sent_by = $user;
		    			$event_registration->save();

		    			$contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $event_id)->select('contact_id')->get())->get();

		    			$setting = Setting::where('name', '=', 'API_KEY')->first();
		    			if(!$setting) {
		    				return response()->json(['error' => 'Api Key is not found'], 404);
		    			}

		    			for($i = 0; $i < count($contacts); $i++) {
		    				$contact = $contacts[$i];
		    				$sent_message = new SentMessage([
		    					'message' => $request['message'],
		    					'sent_to' => $contact->phone,
		    					'is_sent' => false,
		    					'is_delivered' => false,
		    					'sms_port_id' => $sms_port_id,
		    					'sent_by' => $user,
		    				]);
		    				if(!$sent_message->save()) {
		    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);

		    				}
	    					$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
		    			}
		    			$negarit_message_request = array();
			            $negarit_message_request['API_KEY'] = $setting->value;
			            $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
			            $negarit_message_request['messages'] = $insert;

			            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
			                'api_request/sent_multiple_messages', 
			                json_encode($negarit_message_request));
			            $decoded_response = json_decode($negarit_response);
			            if($decoded_response) { 
			                
			                if(isset($decoded_response->status)) {
			                    $sent_message->is_sent = true;
			                    $sent_message->is_delivered = true;
			                    $sent_message->update();
			                    return response()->json(['response' => $decoded_response], 200);
			                }
			                else {
			                    return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			                }
			            } else {
			                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			            }
    				} else {
    					return response()->json(['error' => 'sms port is not found'], 404);
    				}
    			} else {
    				return response()->json(['error' => 'event is not found'], 404);
    			}

            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendRegistrationFormForSingleContact() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_registration_title', 'port_name', 'sent_to','message');
    			$rule = [
    				'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'port_name' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    				'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if($sms_port instanceof SmsPort) {
    				$sms_port_id = $sms_port->id;

    				$sent_message = new SentMessage([
    					'message' => $request['message'],
    					'sent_to' => $request['sent_to'],
    					'is_sent' => false,
    					'is_delivered' => false,
    					'sms_port_id' => $sms_port_id,
    					'sent_by' => $user,
    				]);
    				if($sent_message->save()) {
    					$setting = Setting::where('name', '=', 'API_KEY')->first();
		                if(!$setting) {
		                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
		                }
		                $message_send_request = array();
		                $message_send_request['API_KEY'] = $setting->value;
		                $message_send_request['message'] = $sent_message->message;
		                $message_send_request['sent_to'] = $sent_message->sent_to;
		                $message_send_request['campaign_id'] = $sms_port->negarit_campaign_id;

		                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                        'api_request/sent_message?API_KEY?='.$setting->value, 
                        json_encode($message_send_request));
		                $decoded_response = json_decode($negarit_response);
		                if($decoded_response) { 
		                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
		                        $sent_message->is_sent = true;
		                        $sent_message->is_delivered = true;
		                        $sent_message->update();
		                        return response()->json(['message' => 'message sent successfully',
		                        'sent message' => $decoded_response], 200);
		                    } else {
		                    	return response()->json(['response' => $decoded_response], 500);
		                    }
		                    
		                } else {
		                	return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
		                }
		                
    				} else {
    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent, please send again'], 500);
    				}
    			} else {
    				return response()->json(['error' => 'sms port is not found'], 404);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['messag' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function getEventRegistrationForm($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event_registration = EventRegistration::find($id);
    			if(!$event_registration) {
    				return response()->json(['error' => 'event registration is not found'], 404);
    			}
    			return response()->json(['event registration' => $event_registration], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function getEventRegistrationForms() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event_registrations = new EventRegistration();
    			return response()->json(['event registrations' => $event_registrations::paginate(10)], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
}