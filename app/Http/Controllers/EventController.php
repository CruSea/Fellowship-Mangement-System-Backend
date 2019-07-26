<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use App\Contact;
use App\ContactTeam;
use App\ContactEvent;
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
    			$event->description = $request['event_description'];
                $event->fellowship_id = $user->fellowship_id;
                $event->created_by = json_encode($user);
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
                    $event->created_by = json_decode($event->created_by);
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
    			$events = Event::paginate(10);
    			$count_event = Event::count();
    			if($count_event == 0) {
    				return response()->json(['message' => 'event is empty'], 404);
    			}
                for($i = 0; $i < $count_event; $i++) {
                    $events[$i]->created_by = json_decode($events[$i]->created_by);
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
    public function addContact(Request $request, $name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $rule = [
                    'full_name' => 'required|string|max:255',
                    'gender' => 'required|string|max:255',
                    'acadamic_department' => 'string|max:255',
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                    'email' => 'required|email|max:255|unique:contacts',
                    'graduation_year' => 'required|string',
                ];
                $validator = Validator::make($request->all(), $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
                }
                $contactEvent = new ContactEvent();
                $event = Event::where('event_name', '=', $name)->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }
                $phone_number  = $request->input('phone');
                $contact0 = Str::startsWith($request->input('phone'), '0');
                $contact9 = Str::startsWith($request->input('phone'), '9');
                $contact251 = Str::startsWith($request->input('phone'), '251');
                if($contact0) {
                    $phone_number = Str::replaceArray("0", ["+251"], $request->input('phone'));
                }
                else if($contact9) {
                    $phone_number = Str::replaceArray("9", ["+2519"], $request->input('phone'));
                }
                else if($contact251) {
                    $phone_number = Str::replaceArray("251", ['+251'], $request->input('phone'));
                }
                // check weather the phone exists before
                $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                if($check_phone_existance) {
                    return response()->json(['error' => 'The phone has already been taken'], 400);
                }
                $contact = new Contact();
                $contact->full_name = $request->input('full_name');
                $contact->gender = $request->input('gender');
                $contact->phone = $phone_number;
                $contact->email = $request->input('email');
                $contact->acadamic_department = $request->input('acadamic_department');
                $contact->graduation_year = $request->input('graduation_year').'-07-30';
                $contact->is_under_graduate = true;
                $contact->is_this_year_gc = false;
                $contact->fellowship_id = $user->fellowship_id;
                $contact->created_by = json_encode($user);
                if($contact->save()) {
                    $event_id = $event->id;
                    $contact_id = $contact->id;
                    $contactExists = ContactEvent::where('contact_id', $contact->id)->first();
                    $contactDuplicationInOneEvent = ContactEvent::where([
                        ['event_id', '=', $event_id],
                        ['contact_id', '=', $contact_id],
                    ])->get();
                    if(count($contactDuplicationInOneEvent) > 0) {
                        return response()->json(['error' => 'duplication error', 'message' => 'contact is already found in '. $event->event_name .' event'], 403);
                    }
                    $contactEvent->event_id = $event_id;
                    $contactEvent->contact_id = $contact_id;
                    if($contactEvent->save()) {
                        return response()->json(['message' => 'contacte assigned event successfully'], 200);
                    }
                    $contact->delete();
                    return response()->json(['message' => 'an error occured', 'error' => "contact doesn't assigned a event, please try again"], 500);
                }
                else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact, please try again'], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function assignContact($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $request = request()->only('phone');
                $rule = [
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
                }
                $phone_number  = $request['phone'];
                $contact0 = Str::startsWith($request['phone'], '0');
                $contact9 = Str::startsWith($request['phone'], '9');
                $contact251 = Str::startsWith($request['phone'], '251');
                if($contact0) {
                    $phone_number = Str::replaceArray("0", ["+251"], $request['phone']);
                }
                else if($contact9) {
                    $phone_number = Str::replaceArray("9", ["+2519"], $request['phone']);
                }
                else if($contact251) {
                    $phone_number = Str::replaceArray("251", ['+251'], $request['phone']);
                }
                $contact = Contact::where('phone', '=', $phone_number)->first();
                if(!$contact) {
                    return response()->json(['error' => 'contact is not found'], 404);
                }
                $event = Event::where('event_name', '=', $name)->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }
                $contact_id = $contact->id;
                $event_id = $event->id;
                $contactExists = ContactEvent::where('contact_id', $contact->id)->first();
                $contactDuplicationInOneEvent = ContactEvent::where([
                    ['event_id', '=', $event_id],
                    ['contact_id', '=', $contact_id],
                ])->get();
                if(count($contactDuplicationInOneEvent) > 0) {
                    return response()->json(['message' => 'contact is already found in '. $event->event_name .' event'], 403);
                }
                $contactEvent = new ContactEvent();
                $contactEvent->event_id = $event->id;
                $contactEvent->contact_id = $contact->id;
                if($contactEvent->save()) {
                    return response()->json(['message' => 'contact assigned '.$event->event_name.' event successfully'], 200);
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'contact is not assigned '.$event->event_name.' event'], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function seeContacts($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $event = Event::where('event_name', '=', $name)->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }
                $event_id = $event->id;
                $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $event_id)->select('contact_id')->get())->get();
                if(!$contacts) {
                    return response()->json(['error' => 'something went wrong'], 404);
                }
                $count = $contacts->count();
                if($count == 0) {
                    return response()->json(['error' => 'contact is not found in '.$event->event_name.' event'], 404);
                }
                return response()->json(['contacts' => $contacts], 200);

            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteContact($name, $id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $contact = Contact::find($id);
                if(!$contact) {
                    return response()->json(['error' => 'contact is not found'], 404);
                } 
                $event = Event::where('event_name', '=', $name)->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }
                $event_id = $event->id;
                $contact_id = $contact->id;
                $is_contact_in_event = ContactEvent::where([['event_id', '=', $event_id], ['contact_id', '=', $contact_id],])->first();
                if(!$is_contact_in_event) {
                    return response()->json(['error' => 'contact is not found in '.$event->event_name.' event'], 404);
                }
                $contact_event = ContactEvent::where([['event_id', '=', $event_id], ['contact_id', '=', $contact_id],]);
                if($contact_event->delete()) {
                    return response()->json(['contact is successfully deleted from '.$event->event_name.' event'], 200);
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'contact is not deleted'], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}