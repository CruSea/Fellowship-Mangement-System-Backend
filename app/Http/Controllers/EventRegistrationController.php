<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
    			$request = request()->only('event', 'port_name', 'team', 'message');
    			$rule = [
    				'event' => 'required|string|min:1',
    				'port_name' => 'required|string|min:1',
    				'team' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$event = Event::where([['event_name', '=', $request['event']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$event) {
    				return response()->json(['error' => 'event is not found'], 404);
    			}
    			$team = Team::where([['name', '=', $request['team']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if($team instanceof Team) {
    				$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    				if($sms_port instanceof SmsPort) {

    					$team_id = $team->id;
		    			$sms_port_id = $sms_port->id;

    					$event_registration = new EventRegistration();
		    			$event_registration->event = $request['event'];
		    			$event_registration->team_id = $team_id;
		    			$event_registration->message = $request['message'];
		    			$event_registration->sent_by = $user;
		    			$event_registration->sent_to = $team->name;
		    			$event_registration->get_fellowship_id = $user->fellowship_id;
		    			$event_registration->save();

		    			
		    			$setting = Setting::where('name', '=', 'API_KEY')->first();
		    			if(!$setting) {
		    				return response()->json(['error' => 'Api Key is not found'], 404);
		    			}

		    			$contacts = Contact::whereIn('id', ContactTeam::where('team_id', '=', $team_id)->select('contact_id')->get())->get();
		    			if(count($contacts) == 0) {
		    				return response()->json(['message' => 'member is not found in '.$team->name.' team'], 404);
		    			}
		    			
		    			$insert = [];
			            $contains_name = Str::contains($request['message'], '{name}');
			            if($contains_name) {
			    			for($i = 0; $i < count($contacts); $i++) {
			    				$contact = $contacts[$i];
			    				$replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
			    				
			    				$sent_message = new SentMessage();
		                        $sent_message->message = $replaceName;
		                        $sent_message->sent_to = $contact->full_name;
		                        $sent_message->is_sent = false;
		                        $sent_message->is_delivered = false;
		                        $sent_message->sms_port_id = $sms_port_id;
		                        $sent_message->fellowship_id = $user->fellowship_id;
		                        $sent_message->sent_by = $user;
			    				if(!$sent_message->save()) {
			    					$sent_message_again = new SentMessage();
		                			$sent_message_again->message = $replaceName;
		                			$sent_message_again->sent_to = $contact->full_name;
		                			$sent_message_again->is_sent = false;
		                			$sent_message_again->is_delivered = false;
		                			$sent_message_again->sms_port_id = $sms_port_id;
		                			$sent_message->fellowship_id = $user->fellowship_id;
		                			$sent_message_again->sent_by = $user;
			                		$sent_message_again->save();
			    					// return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);

			    				}
		    					$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
			    			}
			    		} else {
			    			for($i = 0; $i < count($contacts); $i++) {
			    				$contact = $contacts[$i];
			    				
			    				$sent_message = new SentMessage();
		                        $sent_message->message = $request['message'];
		                        $sent_message->sent_to = $contact->full_name;
		                        $sent_message->is_sent = false;
		                        $sent_message->is_delivered = false;
		                        $sent_message->sms_port_id = $sms_port_id;
		                        $sent_message->fellowship_id = $user->fellowship_id;
		                        $sent_message->sent_by = $user;
			    				if(!$sent_message->save()) {
			    					$sent_message_again = new SentMessage();
		                			$sent_message_again->message = $request['message'];
		                			$sent_message_again->sent_to = $contact->full_name;
		                			$sent_message_again->is_sent = false;
		                			$sent_message_again->is_delivered = false;
		                			$sent_message_again->sms_port_id = $sms_port_id;
		                			$sent_message->fellowship_id = $user->fellowship_id;
		                			$sent_message_again->sent_by = $user;
			                		$sent_message_again->save();

			    				}
		    					$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
			    			}
			    		}
			    		if($insert == []) {
			                $team_message->delete();
			                return response()->json(['message' => 'member is not found in '.$team->name. ' team'], 404);
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
    			$request = request()->only('event', 'port_name', 'message');
    			$rule = [
    				// 'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'event' => 'required|string|min:1',
    				'port_name' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$event = Event::where([['event_name', '=', $request['event']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$event) {
    				return response()->json(['error' => 'event not found'], 400);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if($sms_port instanceof SmsPort) {
    				$fellowship_id = $user->fellowship_id;
    				$sms_port_id = $sms_port->id;

    				$fellowship = Fellowship::find($fellowship_id);

    				$event_registration = new EventRegistration();
	    			$event_registration->event = $request['event'];
	    			$event_registration->fellowship_id = $fellowship_id;
	    			$event_registration->message = $request['message'];
	    			$event_registration->sent_by = $user;
	    			$event_registration->sent_to = $fellowship->university_name;
	    			$event_registration->get_fellowship_id = $fellowship_id;
	    			$event_registration->save();

	    			$contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

	    			if(count($contacts) == 0) {
	                    return response()->json(['message' => 'member is not found in '. $fellowship->university_name. ' fellowship'], 404);
	                }

	    			$setting = Setting::where('name', '=', 'API_KEY')->first();
	                if(!$setting) {
	                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
	                }
	                $insert = [];
	                $contains_name = Str::contains($request['message'], '{name}');
	                if($contains_name) {
		                for($i = 0; $i < count($contacts); $i++) {
		                	$contact = $contacts[$i];
		                	$replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
		                	if($contact->is_under_graduate) {
		                		
		                		$sent_message = new SentMessage();
		                        $sent_message->message = $replaceName;
		                        $sent_message->sent_to = $contact->full_name;
		                        $sent_message->is_sent = false;
		                        $sent_message->is_delivered = false;
		                        $sent_message->sms_port_id = $sms_port_id;
		                        $sent_message->fellowship_id = $user->fellowship_id;
		                        $sent_message->sent_by = $user;
		                		if(!$sent_message->save()) {
		                			$sent_message_again = new SentMessage();
		                			$sent_message_again->message = $replaceName;
		                			$sent_message_again->sent_to = $contact->full_name;
		                			$sent_message_again->is_sent = false;
		                			$sent_message_again->is_delivered = false;
		                			$sent_message_again->sms_port_id = $sms_port_id;
		                			$sent_message->fellowship_id = $user->fellowship_id;
		                			$sent_message_again->sent_by = $user;
			                		$sent_message_again->save();
		                		}

		                		$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
		                	}
		                }
		            } else {
		            	for($i = 0; $i < count($contacts); $i++) {
		                	
		                	$contact = $contacts[$i];
		                	if($contact->is_under_graduate) {
		                		
		                		$sent_message = new SentMessage();
		                        $sent_message->message = $request['message'];
		                        $sent_message->sent_to = $contact->full_name;
		                        $sent_message->is_sent = false;
		                        $sent_message->is_delivered = false;
		                        $sent_message->sms_port_id = $sms_port_id;
		                        $sent_message->fellowship_id = $user->fellowship_id;
		                        $sent_message->sent_by = $user;
		                		if(!$sent_message->save()) {
		                			$sent_message_again = new SentMessage();
		                			$sent_message_again->message = $request['message'];
		                			$sent_message_again->sent_to = $contact->full_name;
		                			$sent_message_again->is_sent = false;
		                			$sent_message_again->is_delivered = false;
		                			$sent_message_again->sms_port_id = $sms_port_id;
		                			$sent_message->fellowship_id = $user->fellowship_id;
		                			$sent_message_again->sent_by = $user;
			                		$sent_message_again->save();
		                		}

		                		$insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
		                	}
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
    public function sendRegistrationFormForSingleContact() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event', 'port_name', 'sent_to','message');
    			$rule = [
    				// 'event_registration_title' => 'required|string|min:1|unique:event_registrations',
    				'event' => 'required|string|min:1',
    				'port_name' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    				'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$event = Event::where([['event_name', '=', $request['event']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$event) {
    				return response()->json(['error' => 'event not found'], 404);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if($sms_port instanceof SmsPort) {
    				$sms_port_id = $sms_port->id;
    				$phone_number  = $request['sent_to'];
	                $contact0 = Str::startsWith($request['sent_to'], '0');
	                $contact9 = Str::startsWith($request['sent_to'], '9');
	                $contact251 = Str::startsWith($request['sent_to'], '251');
	                if($contact0) {
	                    $phone_number = Str::replaceArray("0", ["+251"], $request['sent_to']);
	                }
	                else if($contact9) {
	                    $phone_number = Str::replaceArray("9", ["+2519"], $request['sent_to']);
	                }
	                else if($contact251) {
	                    $phone_number = Str::replaceArray("251", ['+251'], $request['sent_to']);
	                }
	                if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
		            	return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
		            }
	                $contains_name = Str::contains($request['message'], '{name}');
	                $contact = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->first();
	                $sent_to = $phone_number;
	                if($contact instanceof Contact) {
		    			$sent_to = $contact->full_name;
	                	if($contains_name) {
		                    $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
		    				
		    				$sent_message = new SentMessage();
	                        $sent_message->message = $replaceName;
	                        $sent_message->sent_to = $contact->full_name;
	                        $sent_message->is_sent = false;
	                        $sent_message->is_delivered = false;
	                        $sent_message->sms_port_id = $sms_port_id;
	                        $sent_message->fellowship_id = $user->fellowship_id;
	                        $sent_message->sent_by = $user;
		    			} else {
		    				$sent_message = new SentMessage();
	                        $sent_message->message = $request['message'];
	                        $sent_message->sent_to = $contact->full_name;
	                        $sent_message->is_sent = false;
	                        $sent_message->is_delivered = false;
	                        $sent_message->sms_port_id = $sms_port_id;
	                        $sent_message->fellowship_id = $user->fellowship_id;
	                        $sent_message->sent_by = $user;
		    			}
	    			} else {
	    				$sent_message = new SentMessage();
                        $sent_message->message = $request['message'];
                        $sent_message->sent_to = $phone_number;
                        $sent_message->is_sent = false;
                        $sent_message->is_delivered = false;
                        $sent_message->sms_port_id = $sms_port_id;
                        $sent_message->fellowship_id = $user->fellowship_id;
                        $sent_message->sent_by = $user;
	    			}
	    			$event_registration = new EventRegistration();
	    			$event_registration->event = $request['event'];
	    			$event_registration->phone = $phone_number;
	    			$event_registration->sent_to = $sent_to;
	    			$event_registration->message = $request['message'];
	    			$event_registration->sent_by = $user;
	    			$event_registration->get_fellowship_id = $user->fellowship_id;
	    			$event_registration->save();

    				if($sent_message->save()) {
    					$setting = Setting::where('name', '=', 'API_KEY')->first();
		                if(!$setting) {
		                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
		                }
		                $message_send_request = array();
		                $message_send_request['API_KEY'] = $setting->value;
		                $message_send_request['message'] = $sent_message->message;
		                $message_send_request['sent_to'] = $phone_number;
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
    			if($event_registration instanceof EventRegistration && $event_registration->get_fellowship_id == $user->fellowship_id) {
    				$event_registration->sent_by = json_decode($event_registration->sent_by);
    				return response()->json(['event registration' => $event_registration], 200);
    			} else {
    				return response()->json(['error' => 'event registration is not found'], 404);
    			}
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
    			// $event_registrations = EventRegistration::paginate(10);
    			$event_registrations = EventRegistration::where('get_fellowship_id', '=', $user->fellowship_id)->paginate(10);
    			$count = $event_registrations->count();
    			if($count == 0) {
    				return response()->json(['response' => 'event registration not found'], 404);
    			}
    			for($i = 0; $i < $count; $i++) {
    				$event_registrations[$i]->sent_by = json_decode($event_registrations[$i]->sent_by);
    			}
    			return response()->json(['event registrations' => $event_registrations], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function deleteEventRegistrationForm($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event_registration = EventRegistration::find($id);
    			if($event_registration instanceof EventRegistration && $event_registration->get_fellowship_id == $user->fellowship_id) {
    				if($event_registration->delete()) {
    					return response()->json(['message' => 'event registration message deleted successfully'], 200);
    				} else {
    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'event registration message is not deleted'], 500);
    				}
    			} else {
    				return response()->json(['error' => 'event registration not found'], 404);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
}