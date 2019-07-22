<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\ScheduleMessage;
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
    			$team = Team::where('name', '=', $request['team'])->first();
    			if(!$team) {
    				return response()->json(['error' => 'team is not found'], 404);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$team_id = $team->id;
    			$sms_port_id = $sms_port->id;

    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->team_id = $team_id;
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
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$sms_port_id = $sms_port->id;

    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->fellowship_id = $user->fellowship_id;
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
    			$event = Event::where('event_name', '=', $request['event'])->first();
    			if(!$event) {
    				return response()->json(['error' => 'event is not found'], 404);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
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
	                'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:schedule_messages',
    				'message' => 'required|string|min:1',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			if(Carbon::parse($request['start_date'])->diffInDays(Carbon::parse($request['end_date']), false) < 0) {
    				return response()->json(['error' => "end date can't be sooner than start date"], 400);
    			}
    			$shceduled_message = new ScheduleMessage();
    			$shceduled_message->type = $request['type'];
    			$shceduled_message->start_date = $request['start_date'];
    			$shceduled_message->end_date = $request['end_date'];
    			$shceduled_message->sent_time = $request['sent_time'];
    			$shceduled_message->message = $request['message'];
    			$shceduled_message->sent_to = $request['sent_to'];
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
    			if($scheduled_message instanceof ScheduleMessage) {
    				return response()->json(['scheduled message' => $scheduled_message], 200);
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
    			$scheduled_messages = new ScheduleMessage();
    			$count_message = $scheduled_messages->count();
    			if($count_message == 0) {
    				return response()->json(['message' => 'scheduled message is empty'], 404);
    			}
    			return response()->json(['scheduled messages' => $scheduled_messages->paginate(10)], 200);
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
    			if($scheduled_message instanceof ScheduleMessage) {

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
    			if($scheduled_message instanceof ScheduleMessage) {
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
