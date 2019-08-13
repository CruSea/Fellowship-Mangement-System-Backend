<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\User;
use App\AlarmMessage;
use App\Fellowship;
use App\Contact;
use App\Team;
use App\Event;
use App\SmsPort;
use App\Setting;
use App\ContactTeam;
use App\contactEvent;
use Carbon\Carbon;
use JWTAuth;
use Input;

class AlarmMessageController extends Controller
{
	public function addMessageForTeam() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('port_name', 'send_date', 'send_time', 'team','message');
    			$rule = [
					'port_name' => 'required|string|max:255',
    				'send_date' => 'required|date_format:Y-m-d|after:today',
    				'send_time' => 'required|date_format:H:i',
    				'team' => 'required|string|min:1',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$team = Team::where([['name', '=', $request['team']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$team) {
    				return response()->json(['error' => 'team is not found'], 404);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			$team_id = $team->id;
                $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                $team_id)->select('contact_id')->get())->get();

                if(count($contacts) == 0) {
                    return response()->json(['message' => 'member is not found in '.$team->name. ' team'], 404);
                }

    			$sms_port_id = $sms_port->id;
    			$get_fellowship_id = $user->fellowship_id;

                $api_key = $sms_port->api_key;
                // check stting existance
                $setting = Setting::where([['name', '=', 'API_KEY'],['value', '=', $api_key], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if(!$setting) {
                    return response()->json(['error' => 'API_KEY is not found'], 404);
                }
    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->team_id = $team_id;
    			$alaram_message->sent_to = $team->name.' team';
    			$alaram_message->sms_port_id = $sms_port_id;
    			$alaram_message->get_fellowship_id = $get_fellowship_id;
    			$alaram_message->sent_by = $user;
    			if($alaram_message->save()) {
    				return response()->json(['message' => 'message scheduled for '. $alaram_message->send_date. ' successfully'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'scheduled message is not sent, please try again'], 500);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function addMessageForFellowship() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('port_name', 'send_date', 'send_time','message');
    			$rule = [
					'port_name' => 'required|string|max:255',
    				'send_date' => 'required|date_format:Y-m-d|after:today',
    				'send_time' => 'required|date_format:H:i',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}

    			$sms_port_id = $sms_port->id;
    			$fellowship_id = $user->fellowship_id;
    			$fellowship = Fellowship::find($fellowship_id);

                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

                if(count($contacts) == 0) {
                    return response()->json(['message' => 'member is not found in '. $fellowship->university_name. ' fellowship'], 404);
                }

                $api_key = $sms_port->api_key;
                // check stting existance
                $setting = Setting::where([['name', '=', 'API_KEY'],['value', '=', $api_key], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if(!$setting) {
                    return response()->json(['error' => 'API_KEY is not found'], 404);
                }
    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->fellowship_id = $fellowship_id;
    			$alaram_message->sent_to = $fellowship->university_name;
    			$alaram_message->sms_port_id = $sms_port_id;
    			$alaram_message->get_fellowship_id = $fellowship_id;
    			$alaram_message->sent_by = $user;
    			if($alaram_message->save()) {
    				return response()->json(['message' => 'message scheduled for '. $alaram_message->send_date .' successfully'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'scheduled message is not sent, please try again'], 500);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function addMessageForEvent() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('port_name', 'send_date', 'send_time', 'event','message');
    			$rule = [
					'port_name' => 'required|string|max:255',
    				'send_date' => 'required|date_format:Y-m-d|after:today',
    				'send_time' => 'required|date_format:H:i',
    				'event' => 'required|string|min:1',
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
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}

    			$event_id = $event->id;
    			$sms_port_id = $sms_port->id;
    			$get_fellowship_id = $user->fellowship_id;

                $contacts = Contact::whereIn('id', contactEvent::where('event_id','=', 
                $event_id)->select('contact_id')->get())->get();

                if(count($contacts) == 0) {
                    return response()->json(['message' => 'member is not found in '.$event->event_name. ' team'], 404);
                }

                $api_key = $sms_port->api_key;
                // check stting existance
                $setting = Setting::where([['name', '=', 'API_KEY'],['value', '=', $api_key], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if(!$setting) {
                    return response()->json(['error' => 'API_KEY is not found'], 404);
                }

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->event_id = $event_id;
    			$alaram_message->sms_port_id = $sms_port_id;
    			$alaram_message->sent_to = $event->event_name.' event';
    			$alaram_message->get_fellowship_id = $get_fellowship_id;
    			$alaram_message->sent_by = $user;
    			if($alaram_message->save()) {
    				return response()->json(['message' => 'message scheduled for '. $alaram_message->send_date. ' successfully'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'scheduled message is not sent, please try again'], 500);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function addMessageForSingleContact() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('port_name', 'send_date', 'send_time', 'sent_to','message');
    			$rule = [
					'port_name' => 'required|string|max:255',
    				'send_date' => 'required|date_format:Y-m-d|after:today',
    				'send_time' => 'required|date_format:H:i',
    				'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:alarm_messages',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			$sms_port_id = $sms_port->id;

                $api_key = $sms_port->api_key;
                // check stting existance
                $setting = Setting::where([['name', '=', 'API_KEY'],['value', '=', $api_key], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if(!$setting) {
                    return response()->json(['error' => 'API_KEY is not found'], 404);
                }

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
	            $phone = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->first();
	            if($phone instanceof Contact) {
	            	$phone_number = $phone->full_name;
	            }
	            $get_fellowship_id = $user->fellowship_id;

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->phone = $phone_number;
    			$alaram_message->sent_to = $phone_number;
    			$alaram_message->sms_port_id = $sms_port_id;
    			$alaram_message->get_fellowship_id = $get_fellowship_id;
    			$alaram_message->sent_by = $user;
    			if($alaram_message->save()) {
    				return response()->json(['message' => 'message scheduled for '. $alaram_message->send_date. ' successfully'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'scheduled message is not sent, please try again'], 500);
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
    			$alarm_message = AlarmMessage::find($id);
    			if($alarm_message instanceof AlarmMessage && $alarm_message->get_fellowship_id == $user->fellowship_id) {
    				$alarm_message->sent_by = json_decode($alarm_message->sent_by);
    				return response()->json(['scheduled_message' => $alarm_message], 200);
    			} else {
    				return response()->json(['error' => 'scheduled message is not found'], 404);
    			}
    		}
	    	else {
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
    			// $alarm_message = AlarmMessage::paginate(10);
    			$alarm_message = AlarmMessage::where('get_fellowship_id', '=', $user->fellowship_id)->paginate(10);
    			$count_message = $alarm_message->count();
    			if($count_message == 0) {
    				return response()->json(['message' => 'scheduled message is empty'], 404);
    			}
    			for($i = 0; $i < $count_message; $i++) {
    				$alarm_message[$i]->sent_by = json_decode($alarm_message[$i]->sent_by);
    			}
    			return response()->json(['scheduled_messages' => $alarm_message], 200);
    		}
	    	else {
	    			return response()->json(['error' => 'token expired'], 401);
	    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function updateMessage($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('send_date', 'send_time', 'message');
    			$rule = [
    				'send_date' => 'required|date_format:Y-m-d|after:yesterday',
    				'send_time' => 'required|date_format:H:i',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			
    			$alaram_message = AlarmMessage::find($id);
    			if($alaram_message instanceof AlarmMessage && $alaram_message->get_fellowship_id == $user->fellowship_id) {
    				$alaram_message->send_date = isset($request['send_date']) ? $request['send_date'] : $alaram_message->send_date;
    				$alaram_message->send_time = isset($request['send_time']) ? $request['send_time'] : $alaram_message->send_time;
    				$alaram_message->message = isset($request['message']) ? $request['message'] : $alaram_message->message;
    				$alaram_message->get_fellowship_id = $alaram_message->get_fellowship_id;
    				if($alaram_message->update()) {
    					return response()->json(['message' => 'scheduled message updated successfully'], 200);
    				} else {
    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'scheduled message is not updated'], 500);
    				}
    			} else {
    				return response()->json(['error' => 'scheduled message is not found'], 404);
    			}
    		}
	    	else {
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
    			$alaram_message = AlarmMessage::find($id);
    			if($alaram_message instanceof AlarmMessage && $alaram_message->get_fellowship_id == $user->fellowship_id) {
    				if($alaram_message->delete()) {
    					return response()->json(['response' => 'scheduled message deleted successfully'], 200);
    				} else {
    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'shceduled message is not deleted'], 200);
    				}
    			} else {
    				return response()->json(['error' => 'scheduled message is not found'], 404);
    			}
    		}
	    	else {
	    			return response()->json(['error' => 'token expired'], 401);
	    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function searchAlarmMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $search = Input::get('search');
                if($search) {

                    $messages = AlarmMessage::where([['message', 'LIKE', '%'.$search.'%'], ['get_fellowship_id', '=', $user->fellowship_id]])->get();
                    if(count($messages) > 0) {
                        for($i = 0; $i < count($messages); $i++) {
                            $messages[$i]->sent_by = json_decode($messages[$i]->sent_by);
                        }
                        return $messages;
                    }
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
}
