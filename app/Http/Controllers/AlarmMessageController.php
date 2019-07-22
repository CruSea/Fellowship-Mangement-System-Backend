<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\AlarmMessage;
use App\Team;
use App\Event;
use App\SmsPort;
use Carbon\Carbon;
use JWTAuth;

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
    			$team = Team::where('name', '=', $request['team'])->first();
    			if(!$team) {
    				return response()->json(['error' => 'team is not found'], 404);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			$team_id = $team->id;
    			$sms_port_id = $sms_port->id;

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->team_id = $team_id;
    			$alaram_message->sms_port_id = $sms_port_id;
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
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}

    			$sms_port_id = $sms_port->id;

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->fellowship_id = $user->fellowship_id;
    			$alaram_message->sms_port_id = $sms_port_id;
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
    			$event = Event::where('event_name', '=', $request['event'])->first();
    			if(!$event) {
    				return response()->json(['error' => 'event is not found'], 404);
    			}
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			$event_id = $event->id;
    			$sms_port_id = $sms_port->id;

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->event_id = $event_id;
    			$alaram_message->sms_port_id = $sms_port_id;
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
    			$sms_port = SmsPort::where('port_name', '=', $request['port_name'])->first();
    			if(!$sms_port) {
    				return  response()->json(['error' => 'sms port is not found'], 404);
    			}
    			$sms_port_id = $sms_port->id;

    			$alaram_message = new AlarmMessage();
    			$alaram_message->send_date = $request['send_date'];
    			$alaram_message->send_time = $request['send_time'];
    			$alaram_message->message = $request['message'];
    			$alaram_message->sent_to = $request['sent_to'];
    			$alaram_message->sms_port_id = $sms_port_id;
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
    			$alaram_message = AlarmMessage::find($id);
    			if($alaram_message instanceof AlarmMessage) {
    				return response()->json(['scheduled message' => $alaram_message], 200);
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
    			$alaram_message = new AlarmMessage();
    			$count_message = $alaram_message->count();
    			if($count_message == 0) {
    				return response()->json(['message' => 'scheduled message is empty'], 404);
    			}
    			return response()->json(['scheduled messages' => $alaram_message->paginate(10)], 200);
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
    			if($alaram_message instanceof AlarmMessage) {

    				$alaram_message->send_date = isset($request['send_date']) ? $request['send_date'] : $alaram_message->send_date;
    				$alaram_message->send_time = isset($request['send_time']) ? $request['send_time'] : $alaram_message->send_time;
    				$alaram_message->message = isset($request['message']) ? $request['message'] : $alaram_message->message;
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
    			if($alaram_message instanceof AlarmMessage) {
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
}
