<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\SmsPort;
use App\Contact;
use App\ContactTeam;
use App\ContactEvent;
use App\SentMessage;
use App\EventRegistration;
use App\Setting;
use App\RegistrationEventMessage;
use JWTAuth;

class SendMessageForEventRegistrationController extends Controller
{
	protected $negarit_api_url;
	public function __construct() {
		$this->negarit_api_url = 'http://api.negarit.net/api/';
	}
	public function sendMessage() {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$request = request()->only('sms_port', 'event_registration_title', 'message');
				$rule = [
					'sms_port' => 'required|string|min:1',
					'event_registration_title' => 'required|string|min:1',
					'message' => 'required|string|min:1',
				];
				$validator = Validator::make($request, $rule);
				if($validator->fails()) {
					return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
				}
				$sms_port = SmsPort::where('port_name', '=', $request['sms_port'])->first();
				if(!$sms_port) {
					return response()->json(['error' => 'sms port is not found'], 404);
				}
				$event_registration = EventRegistration::where('event_registration_title', '=', $request['event_registration_title'])->first();
				if(!$event_registration) {
					return response()->json(['error' => 'event registration is not found'], 404);
				}
				$event_registration_id = $event_registration->id;
				$sms_port_id = $sms_port->id;

				// get api key from setting table
                $setting = Setting::where('name', '=', 'API_KEY')->first();
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }

                $getSmsPort = SmsPort::find($sms_port_id);
                if(!$getSmsPort) {
                    return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
                }

                // send message for a single contact who is registered for the event
                $sent_to = $event_registration->sent_to;

				if($sent_to != null) {
					$sent_event_message = new RegistrationEventMessage();
					$sent_event_message->message = $request['message'];
					$sent_event_message->event_registrations_id = $event_registration_id;
					$sent_event_message->sms_port_id = $sms_port_id;
					$sent_event_message->sent_by = $user;
					
					if($sent_event_message->save()) {
						$sentMessage = new SentMessage([
							'message' => $sent_event_message->message,
							'sent_to' => $sent_to,
							'is_sent' => false,
							'is_delivered' => false,
							'sms_port_id' => $sms_port_id,
							'sent_by' => $user,
						]);
						if($sentMessage->save()) {

							$get_campaign_id = $getSmsPort->negarit_campaign_id;
			                $get_api_key = $getSmsPort->negarit_sms_port_id;
			                $get_message = $sentMessage->message;
			                $get_phone = $sent_to;
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
						} else {
							return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message not sent, please try again'], 500);
						}
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent, please try again'], 200);
					}
				}
				// send message for team if team is registered for the event 
				$team_id = $event_registration->team_id;
				if($team_id != null) {
					$sent_event_message = new RegistrationEventMessage();
					$sent_event_message->message = $request['message'];
					$sent_event_message->event_registrations_id = $event_registration_id;
					$sent_event_message->sms_port_id = $sms_port_id;
					$sent_event_message->sent_by = $user;

					$contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
				            $team_id)->select('contact_id')->get())->get();

					if($sent_event_message->save()) {

						for($i = 0; $i< count($contacts); $i++) {
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
			                        return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
			                    }
			                    $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
			                }

						}
						$negarit_message_request = array();
			            $negarit_message_request['API_KEY'] = $setting->value;
			            $negarit_message_request['campaign_id'] = $getSmsPort->negarit_campaign_id;
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
			                    return response()->json(['response' => $decoded_response], 500);
			                }
			            } else {
			                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			            }
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
					}
					
				}
				// send message for fellowship if fellowship is registered for the event
				$fellowship_id = $event_registration->fellowship_id;
				if($fellowship_id != null) {
					$sent_event_message = new RegistrationEventMessage();
					$sent_event_message->message = $request['message'];
					$sent_event_message->event_registrations_id = $event_registration_id;
					$sent_event_message->sms_port_id = $sms_port_id;
					$sent_event_message->sent_by = $user;

					$contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

					if($sent_event_message->save()) {

						for($i = 0; $i< count($contacts); $i++) {
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
			                        return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
			                    }
			                    $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
			                }

						}
						$negarit_message_request = array();
			            $negarit_message_request['API_KEY'] = $setting->value;
			            $negarit_message_request['campaign_id'] = $getSmsPort->negarit_campaign_id;
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
			                    return response()->json(['response' => $decoded_response], 500);
			                }
			            } else {
			                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			            }
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
					}
				}
				// send message for event if event is registered for the event
				$event_id = $event_registration->event_id;
				if($event_id != null) {
					$sent_event_message = new RegistrationEventMessage();
					$sent_event_message->message = $request['message'];
					$sent_event_message->event_registrations_id = $event_registration_id;
					$sent_event_message->sms_port_id = $sms_port_id;
					$sent_event_message->sent_by = $user;

					$contacts = Contact::whereIn('id', ContactEvent::where('event_id','=', 
				            $event_id)->select('contact_id')->get())->get();

					if($sent_event_message->save()) {

						for($i = 0; $i< count($contacts); $i++) {
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
			                        return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
			                    }
			                    $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $sent_message->sent_to];
			                }

						}
						$negarit_message_request = array();
			            $negarit_message_request['API_KEY'] = $setting->value;
			            $negarit_message_request['campaign_id'] = $getSmsPort->negarit_campaign_id;
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
			                    return response()->json(['response' => $decoded_response], 500);
			                }
			            } else {
			                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
			            }
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not sent'], 500);
					}
				}
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
	}
	public function getMessage($id) {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$message = RegistrationEventMessage::find($id);
				if($message instanceof RegistrationEventMessage) {
					return response()->json(['message' => $message], 200);
				} else {
					return response()->json(['error' => 'message is not found'], 404);
				}
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
	}
	public function getMessages() {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$messages = RegistrationEventMessage::paginate(10);
				if(!$messages) {
					return response()->json(['error' => 'Oops! something went wrong'], 500);
				}
				$countMessage = count($messages);
				if($countMessage == 0) {
					return response()->json(['response' => 'message is empty'], 404);
				}
				return response()->json(['message' => $messages], 200);
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
	}
	public function deleteMessage($id) {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$message = RegistrationEventMessage::find($id);
				if($message instanceof RegistrationEventMessage) {
					if($message->delete()) {
						return response()->json(['message' => 'message deleted successfully'], 200);
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not deleted'], 500);
					}
				} else {
					return response()->json(['error' => 'message is not found'], 404);
				}
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
	}
}

		// try {
		// 	$user = JWTAuth::parseToken()->toUser();
		// 	if($user instanceof User) {

		// 	} else {
		// 		return response()->json(['error' => 'token expired'], 401);
		// 	}
		// } catch(Exception $ex) {
		// 	return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		// }
