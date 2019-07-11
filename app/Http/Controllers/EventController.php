<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Contact;
use App\ContactTeam;
use App\Team;
use App\Event;
use App\Fellowship;
use JWTAuth;

class EventController extends Controller
{
    public function store() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_name', 'event_description');
    			$rule = [
    				'event_name' => 'required|string|min:1|unique:events',
    				'event_description' => 'string',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			$event = new Event();
    			$event->event_name = $request['event_name'];
    			$event->event_description = $request['event_description'];
    			if($event->save()) {
    				return response()->json(['message' => 'event saved successfully'], 200);
    			}
    			return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'event is not saved'], 500);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function show($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event = Event::find($id);
    			if($event instanceof Event) {
    				return response()->json(['event' => $event], 200);
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
    public function getEvents() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$events = Event::all();
    			$count_event = count($events);
    			if($count_event == 0) {
    				return response()->json(['message' => 'event is empty'], 404);
    			}
    			return response()->json(['events' => $events], 200);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function update($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_name', 'event_description');
    			$rule = [
    				'event_name' => 'string|min:1',
    				'event_description' => 'string|min:1'
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->fails()], 401);
    			}
    			$event = Event::find($id);
    			if($event instanceof Event) {
    				$event->event_name = isset($request['event_name']) ? $request['event_name'] : $event->event_name;
    				$event->event_description = isset($request['event_description']) ? $request['event_description'] : $event->event_description;
    				if($event->update()) {
    					return response()->json(['message' => 'event updated successfully'], 200);
    				}
    				else {
    					return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'event is not updated'], 500);
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
    public function delete($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$event = Event::find($id);
    			if($event instanceof Event) {
    				if($event->delete()) {
    					return response()->json(['message' => 'event deleted successfully'],200);
    				}
    				return response()->json(['message' => 'Ooops! something went wrong', 
    						'error' => 'event is not deleted'], 500);
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
}