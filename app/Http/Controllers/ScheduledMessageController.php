<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\User;
use App\ScheduleMessage;
use App\Fellowship;
use App\Contact;
use App\Team;
use App\Event;
use App\SmsPort;
use Carbon\Carbon;
use JWTAuth;

class ScheduledMessageController extends Controller
{
    public function addMessageForTeam() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('port_name', 'type', 'start_date', 'end_date', 'sent_time', 'team','message');
    			$rule = [
					'port_name' => 'required|string|max:255',
    				'type' => 'required|string|min:1',
    				'start_date' => 'required|date_format:Y-m-d|after:today',
    				'end_date' => 'required|date_format:Y-m-d|after:tomorrow',
    				'sent_time' => 'required|date_format:H:i',
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
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$team_id = $team->id;
    			$sms_port_id = $sms_port->id;
    	// 		$key = str_random(60);
    	// 		// check key existance before, key must be unique
    	// 		$key_exist = ScheduleMessage::where('key', '=', $key)->first();
    	// 		if($key_exist) {
					// $key = str_random(60);
    	// 		}
    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->team_id = $team_id;
    			$shceduled_message->sent_to = $team->name. ' team';
    			$shceduled_message->get_fellowship_id = $user->fellowship_id;
    			$shceduled_message->sms_port_id = $sms_port_id;
    			// $shceduled_message->key = $key;
    			$shceduled_message->sent_by = $user;
    			if($shceduled_message->save()) {
    				return response()->json(['message' => 'message scheduled successfully'], 200);
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
    			$request = request()->only('port_name','type', 'start_date', 'end_date', 'sent_time','message');
    			$rule = [
    				'port_name' => 'required|string|max:255',
    				'type' => 'required|string|min:1',
    				'start_date' => 'required|date_format:Y-m-d|after:yesterday',
    				'end_date' => 'required|date_format:Y-m-d|after:today',
    				'sent_time' => 'required|date_format:H:i',
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
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$sms_port_id = $sms_port->id;
    			$fellowship_id = $user->fellowship_id;
    			$fellowship = Fellowship::find($fellowship_id);

    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->fellowship_id = $fellowship_id;
    			$shceduled_message->sent_to = $fellowship->university_name;
    			$shceduled_message->sms_port_id = $sms_port_id;
    			$shceduled_message->get_fellowship_id = $user->fellowship_id;
    			$shceduled_message->sent_by = $user;
    			if($shceduled_message->save()) {
    				return response()->json(['message' => 'message scheduled successfully'], 200);
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
    			$request = request()->only('port_name', 'type', 'start_date', 'end_date', 'sent_time', 'event','message');
    			$rule = [
    				'port_name' => 'required|string|max:255',
    				'type' => 'required|string|min:1',
    				'start_date' => 'required|date_format:Y-m-d|after:yesterday',
    				'end_date' => 'required|date_format:Y-m-d|after:today',
    				'sent_time' => 'required|date_format:H:i',
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
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$event_id = $event->id;
    			$sms_port_id = $sms_port->id;

    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->event_id = $event_id;
    			$shceduled_message->sent_to = $event->event_name. ' event';
    			$shceduled_message->sms_port_id = $sms_port_id;
    			$shceduled_message->get_fellowship_id = $user->fellowship_id;
    			$shceduled_message->sent_by = $user;
    			if($shceduled_message->save()) {
    				return response()->json(['message' => 'message scheduled successfully'], 200);
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
    			$request = request()->only('port_name', 'type', 'start_date', 'end_date', 'sent_time', 'sent_to','message');
    			$rule = [
    				'port_name' => 'required|string|min:1',
    				'type' => 'required|string|min:1',
    				'start_date' => 'required|date_format:Y-m-d|after:yesterday',
    				'end_date' => 'required|date_format:Y-m-d|after:today',
    				'sent_time' => 'required|date_format:H:i',
	                'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$sms_port = SmsPort::where([['port_name', '=', $request['port_name']], ['fellowship_id', '=', $user->fellowship_id]])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
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

	            $sms_port_id = $sms_port->id;

    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->phone = $phone_number;
    			$shceduled_message->sent_to = $phone_number;
    			$shceduled_message->get_fellowship_id = $user->fellowship_id;
    			$shceduled_message->sms_port_id = $sms_port_id;
    			$shceduled_message->sent_by = $user;
    			if($shceduled_message->save()) {
    				return response()->json(['message' => 'message scheduled successfully'], 200);
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
    			$scheduled_message = ScheduleMessage::find($id);
    			if($scheduled_message instanceof ScheduleMessage && $scheduled_message->get_fellowship_id == $user->fellowship_id) {
    				$scheduled_message->sent_by = json_decode($scheduled_message->sent_by);
    				return response()->json(['scheduled_message' => $scheduled_message], 200);
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
    			// $scheduled_messages = ScheduleMessage::paginate(10);
    			$scheduled_messages = ScheduleMessage::where('get_fellowship_id', '=', $user->fellowship_id)->paginate(10);
    			$count_message = $scheduled_messages->count();
    			if($count_message == 0) {
    				return response()->json(['message' => 'scheduled message is empty'], 404);
    			}
    			for($i = 0; $i < $count_message; $i++) {
    				$scheduled_messages[$i]->sent_by = json_decode($scheduled_messages[$i]->sent_by);
    			}
    			return response()->json(['scheduled_messages' => $scheduled_messages], 200);
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
    			$request = request()->only('type', 'start_date', 'end_date', 'sent_time', 'message');
    			$rule = [
    				'type' => 'required|string|min:1',
    				'start_date' => 'required|date_format:Y-m-d|after:yesterday',
    				'end_date' => 'required|date_format:Y-m-d|after:today',
    				'sent_time' => 'required|date_format:H:i',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			
    			$scheduled_message = ScheduleMessage::find($id);
    			if($scheduled_message instanceof ScheduleMessage && $scheduled_message->get_fellowship_id == $user->fellowship_id) {

    				// check whether message name found before
	                if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
	    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
	    			}

    				$scheduled_message->type = isset($request['type']) ? $request['type'] : $scheduled_message->type;
    				$scheduled_message->start_date = isset($request['start_date']) ? $request['start_date'] : $scheduled_message->start_date;
    				$scheduled_message->end_date = isset($request['end_date']) ? $request['end_date'] : $scheduled_message->end_date;
    				$scheduled_message->sent_time = isset($request['sent_time']) ? $request['sent_time'] : $scheduled_message->sent_time;
    				$scheduled_message->message = isset($request['message']) ? $request['message'] : $scheduled_message->message;
    				if($scheduled_message->update()) {
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
    			$scheduled_message = ScheduleMessage::find($id);
    			if($scheduled_message instanceof ScheduleMessage && $scheduled_message->get_fellowship_id == $user->fellowship_id) {
    				if($scheduled_message->delete()) {
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
}

