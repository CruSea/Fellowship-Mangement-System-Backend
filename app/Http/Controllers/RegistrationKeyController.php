<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\User;
use App\RegistrationKey;
use App\Event;
use JWTAuth;

class RegistrationKeyController extends Controller
{
	public function store() {
		try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('registration_key', 'type', 'event', 'success_message_reply', 'registration_end_date');
    			$rule = [
    				'registration_key' => 'required|string|min:1',
    				'type' => 'required|string|min:1',
    				'event' => 'string|min:1|nullable',
    				'success_message_reply' => 'string|min:1|nullable',
    				'registration_end_date' => 'required|date_format:Y-m-d|after:tomorrow'
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
    			// check whether registration key assigned before
    			$check_key = RegistrationKey::where([['registration_key', '=', $request['registration_key']], ['fellowship_id', '=', $user->fellowship_id]])->exists();
    			if($check_key) {
    				return response()->json(['error' => 'registration key has already been taken', 'message' => "you can't use the same registration key"], 400);
    			}
    			// check registration key contains comma (,)
    			$contains_comma = Str::contains($request['registration_key'], ',');
    			if($contains_comma) {
    				return response()->json(['error' => "registration key can't contain comma (,)"], 400);
    			}
    			$event_name = null;
    			$for_contact_update = false;
    			if($request['type'] == 'event_registration') {
    				$event = Event::where([['event_name', '=', $request['event']], ['fellowship_id', '=', $user->fellowship_id]])->first();
	    			if(!$event) {
	    				return response()->json(['error' => 'event not found'], 400);
	    			}
	    			$event_name = $event->event_name;
    			} else if($request['type'] == 'contact_update') {
    				$for_contact_update = true;
    			} else {
    				return response()->json(['error' => 'type is not valid', 'message' => 'type can be whether event_registration or contact_update'], 400);
    			}
    			$registration_key = new RegistrationKey();
    			$registration_key->registration_key = $request['registration_key'];
    			$registration_key->type = $request['type'];
    			$registration_key->event = $event_name;
    			$registration_key->for_contact_update = $for_contact_update;
    			$registration_key->success_message_reply = $request['success_message_reply'];
    			$registration_key->registration_end_date = $request['registration_end_date'];
    			$registration_key->fellowship_id = $user->fellowship_id;
    			$registration_key->created_by = $user;
    			if($registration_key->save()) {
    				return response()->json(['message' => 'registration key successfully saved'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'registration key is not saved'], 500);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
	}
	public function show($id) {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$registration_key = RegistrationKey::find($id);
				if($registration_key instanceof RegistrationKey && $registration_key->fellowship_id == $user->fellowship_id) {
					$registration_key->created_by = json_decode($registration_key->created_by);
					return response()->json(['registration_key' => $registration_key], 200);
				} else {
					return response()->json(['error' => 'registration key is not found'], 404);
				}
			} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
	}
	public function getRegistrationKeys() {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$registration_key = RegistrationKey::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(10);
				$count = $registration_key->count();
				if($count > 0) {
					for($i = 0; $i < $count; $i++) {
						$registration_key[$i]->created_by = json_decode($registration_key[$i]->created_by);
					}
					return response()->json(['registration_key' => $registration_key], 200);
				} else {
					return response()->json(['registration_key' => $registration_key], 200);
				}
			} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
	}
	public function update($id) {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$registration_key = RegistrationKey::find($id);
				if($registration_key instanceof RegistrationKey && $registration_key->fellowship_id == $user->fellowship_id) {
					$request = request()->only('registration_key', 'event', 'success_message_reply', 'registration_end_date');
	    			$rule = [
	    				'registration_key' => 'required|string|min:1',
	    				'event' => 'string|min:1|nullable',
	    				'success_message_reply' => 'string|min:1|nullable',
	    				'registration_end_date' => 'required|date_format:Y-m-d|after:tomorrow'
	    			];
	    			$validator = Validator::make($request, $rule);
	    			if($validator->fails()) {
	    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
	    			}
	    			// check registration key found before
	    			$check_key = RegistrationKey::where([['registration_key', '=', $request['registration_key']], ['fellowship_id', '=', $user->fellowship_id]])->first();
	    			if($check_key && $registration_key->registration_key != $request['registration_key']) {
	    				return response()->json(['error' => 'registration key has already been taken', 'message' => "you can't use the same registration key"], 400);
	    			}
	    			// check registration key contains comma (,)
	    			$contains_comma = Str::contains($request['registration_key'], ',');
	    			if($contains_comma) {
	    				return response()->json(['error' => "registration key can't contain comma (,)"], 400);
	    			}
	    			$event_name = $registration_key->event;
	    			$for_contact_update = $registration_key->for_contact_update;
	    			if($registration_key->type == 'event_registration') {
		    				$event = Event::where([['event_name', '=', $request['event']], ['fellowship_id', '=', $user->fellowship_id]])->first();
		    			if(!$event) {
		    				return response()->json(['error' => 'event not found'], 400);
		    			}
		    			$event_name = $event->event_name;
	    			} else if($registration_key->type == 'contact_update') {
	    				$for_contact_update = true;
	    			}
	    			$registration_key->registration_key = $request['registration_key'];
	    			$registration_key->event = $event_name;
	    			$registration_key->success_message_reply = $request['success_message_reply'];
	    			$registration_key->registration_end_date = $request['registration_end_date'];
	    			$registration_key->fellowship_id = $user->fellowship_id;
	    			$registration_key->created_by = $user;
	    			if($registration_key->update()) {
	    				return response()->json(['message' => 'registration key updated successfully'], 200);
	    			} else {
	    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'registration key is not updated'], 500);
	    			}
				} else {
					return response()->json(['error' => 'registration key is not found'], 404);
				}
			} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
	}
	public function delete($id) {
		try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$registration_key = RegistrationKey::find($id);
				if($registration_key instanceof RegistrationKey && $registration_key->fellowship_id == $user->fellowship_id) {
					if($registration_key->delete()) {
						return response()->json(['message' => 'registration key deleted successfully'], 200);
					} else {
						return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'registration key is not deleted'], 500);
					}
				} else {
					return response()->json(['error' => 'registration key is not found'], 404);
				}
			} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
	}
}
