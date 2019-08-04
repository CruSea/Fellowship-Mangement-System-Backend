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
use Input;
use Excel;
use JWTAuth;

class EventController extends Controller
{
    public function store() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('event_name', 'event_description');
    			$rule = [
    				'event_name' => 'required|string|min:1',
    				'event_description' => 'string|nullable',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
                $fellowship_event = Event::where('fellowship_id', '=', $user->fellowship_id)->first();
                if($fellowship_event) {
                    if($fellowship_event->event_name == $request['event_name']) {
                        return response()->json(['error' => 'event name is has already been taken'], 400);
                    }
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
    			if($event instanceof Event && $event->fellowship_id == $user->fellowship_id) {
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
    			// $events = Event::paginate(10);
                $events = Event::where('fellowship_id', '=', $user->fellowship_id)->paginate(10);
    			$count_event = $events->count();
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
    				'event_name' => 'required|string|min:1',
    				'event_description' => 'string|min:1|nullable'
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->fails()], 401);
    			}
    			$event = Event::find($id);
                $check_event_name_existance = Event::where([['event_name', '=', $request['event_name']], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                
    			if($event instanceof Event && $event->fellowship_id == $user->fellowship_id) {
                    if($check_event_name_existance && $request['event_name'] != $event->event_name) {
                        return response()->json(['error' => 'event name has already been taken'], 400);
                    }
    				$event->event_name = isset($request['event_name']) ? $request['event_name'] : $event->event_name;
    				$event->description = isset($request['description']) ? $request['event_description'] : $event->description;
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
    			if($event instanceof Event && $event->fellowship_id == $user->fellowship_id) {
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
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                    'email' => 'email|max:255|unique:contacts|nullable',
                    'graduation_year' => 'required|string',
                ];
                $validator = Validator::make($request->all(), $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
                }
                $contactEvent = new ContactEvent();
                $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
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
                if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                    return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
                }
                // check whether contact found in the same fellowship
                $check_contact_exists_in_fellowship = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if($check_contact_exists_in_fellowship) {
                    $get_contact = Contact::where('phone', '=', $phone_number)->first();
                    $event_id = $event->id;
                    $contact_id = $get_contact->id;
                    $contactDuplicationInOneEvent = ContactEvent::where([
                        ['event_id', '=', $event_id],
                        ['contact_id', '=', $contact_id],
                    ])->get();
                    if(count($contactDuplicationInOneEvent) > 0) {
                        return response()->json(['error' => 'duplication error', 'message' => 'contact is already found in '. $event->event_name .' event'], 403);
                    } else {
                        $contactEvent->event_id = $event_id;
                        $contactEvent->contact_id = $contact_id;
                        if($contactEvent->save()) {
                            return response()->json(['message' => 'contact assigned event successfully'], 200);
                        }
                        return response()->json(['message' => 'an error occured', 'error' => "contact doesn't assigned a event, please try again"], 500);
                    }
                }
                // check whether the phone exists before
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
                $contact->created_by = $user->full_name;
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
                if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                    return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
                }
                $contact = Contact::where([['phone', '=', $phone_number],['fellowship_id', '=', $user->fellowship_id]])->first();
                if(!$contact) {
                    return response()->json(['error' => 'contact is not found'], 404);
                }
                $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
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
                $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }
                $event_id = $event->id;
                $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $event_id)->select('contact_id')->get())->paginate(10);
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
                $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
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
    public function importContactForEvent($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$event) {
                return response()->json(['error' => 'event is not found'], 404);
            }
            if($user instanceof User) {
                $count_add_contacts = 0;
                if(Input::hasFile('file')) {
                    $path = Input::file('file')->getRealPath();
                    $data = Excel::load($path, function($reader){
                    })->get();
                    $headerRow = $data->first()->keys();
                    $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3], $headerRow[4], $headerRow[5], $headerRow[6]);
                    if(!empty($data) && $data->count()) {
                        foreach($data as $key => $value) {
                            // phone validation 
                            if($value->phone == null) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => "validation error", 'error' => "phone can't be null"], 403);
                                }
                                return response()->json(['message' => 'validation error', 'error' => "phone can't be null"], 404);
                            }
                            // full_name validation 
                            if($value->full_name == null) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "full name can't be null"], 403);
                                }
                                return response()->json(['message' => 'validatoin error', 'error' => "full name can't be null"], 404);
                            }
                            // gender validatin
                            if($value->gender == null) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "gender can't be null"], 403);
                                }
                                return response()->json(['message' => 'validation error', 'error' => "gender can't be null"], 404);
                            }
                            if($value->acadamic_department == null) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => 'validation error', 'error' => "acadamic department year can't be null"], 404);
                                }
                                return response()->json(['message' => 'validation error', 'error' => "acadamic department year can't be null"], 404);
                            }
                            if($value->graduation_year == null) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => 'validation error', 'error' => "graduation year can't be null"], 404);
                                }
                                return response()->json(['message' => 'validation error', 'error' => "graduation year can't be null"], 404);
                            }
                            $team = Team::where([['name', '=', $value->team], ['fellowship_id', '=', $user->fellowship_id]])->first();
                            if($value->team != null && !$team) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => $value->team.' team is not found, please add '.$value->team.' team first if you want to add contact to '.$value->team.' team'], 400);
                                }
                                return response()->json(['error' => $value->team.' team is not found, please add '.$value->team.' team first if you want to add contact to '.$value->team.' team'], 400);
                            }

                            $phone_number  = $value->phone;
                            $contact0 = Str::startsWith($value->phone, '0');
                            $contact9 = Str::startsWith($value->phone, '9');
                            $contact251 = Str::startsWith($value->phone, '251');
                            if($contact0) {
                                $phone_number = Str::replaceArray("0", ["+251"], $value->phone);
                            }
                            else if($contact9) {
                                $phone_number = Str::replaceArray("9", ["+2519"], $value->phone);
                            }
                            else if($contact251) {
                                $phone_number = Str::replaceArray("251", ['+251'], $value->phone);
                            }
                            if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                            }

                            // check whether the phone exists before
                            $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                            // check whether the email exists before
                            $check_email_existance = Contact::where([['email', '=',$value->email],['email', '!=', null]])->exists();
                            if(!$check_phone_existance && !$check_email_existance&& strlen($phone_number) == 13) {
                                $contact = new Contact();
                                $contact->full_name = $value->full_name;
                                $contact->gender = $value->gender;
                                $contact->phone = $phone_number;
                                $contact->email = $value->email;
                                $contact->acadamic_department = $value->acadamic_department;
                                $contact->graduation_year = $value->graduation_year.'-07-30';
                                $contact->fellowship_id = $user->fellowship_id;
                                $contact->is_under_graduate = true;
                                $contact->is_this_year_gc = false;
                                $contact->created_by = $user->full_name;
                                if($contact->save()) {
                                    $contact_event = new ContactEvent();
                                    $contact_event->event_id = $event->id;
                                    $contact_event->contact_id = $contact->id;
                                    $contact_event->save();
                                    if($value->team != null && $team instanceof Team) {
                                        $contact_team = new ContactTeam();
                                        $contact_team->team_id = $team->id;
                                        $contact_team->contact_id = $contact->id;
                                        $contact_team->save();
                                    }
                                    $count_add_contacts++;
                                }
                            }
                            if($check_phone_existance) {
                                // check whether the contact is found in the same felloship
                                $fellowship_contact = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                                if($fellowship_contact) {
                                    $contact = Contact::where('phone', '=', $phone_number)->first();
                                    $contact_event = new ContactEvent();
                                    $check_member_existance = ContactEvent::where([['event_id', '=', $event->id],['contact_id', '=', $contact->id]])->first();
                                    if(!$check_member_existance) {
                                        $contact_event->event_id = $event->id;
                                        $contact_event->contact_id = $contact->id;
                                        $contact_event->save();
                                        $count_add_contacts++;
                                    }
                                }
                            }
                        }
                        if($count_add_contacts == 0) {
                            dd('member is not added to '.$event->event_name.' event');
                        }
                        dd($count_add_contacts.' contacts added to '.$event->event_name.' event successfully');
                    }
                    else {
                        return response()->json(['message' => 'file is empty', 'error' => 'unable to add contact'], 404);
                    }
                }
                return response()->json(['message' => 'File not found', 'error' => 'Contact file is not provided'], 404);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function exportEventContact($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $event = Event::where([['event_name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if($event instanceof Event) {
                    $event_id = $event->id;
                    $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $event_id)->select('contact_id')->get())->get()->toArray();
                    $count = $contacts->count();
                    if($count == 0) {
                        return response()->json(['message' => 'contact is not found in '.$event->event_name.' event'], 404);
                    }
                    $contact_array[] = array('full_name','gender', 'phone', 'email', 'acadamic_department', 'graduation_year', 'created_by', 'created_at', 'updated_at');
                    foreach ($contacts as $contact) {
                        $contact_array[] = array(
                            'full_name' => $contact->full_name,
                            'gender' => $contact->gender,
                            'phone' => $contact->phone,
                            'email' => $contact->email,
                            'acadamic_department' => $contact->acadamic_department,
                            'graduation_year' => $contact->graduation_year,
                            'created_by' => $contact->created_by,
                            'created_at' => $contact->created_at,
                            'updated_at' => $contact->updated_at,
                        );
                    }
                    Excel::create('contacts', function($excel) use(
                        $contact_array) {
                        $excel->setTitle('contacts');
                        $excel->sheet('contacts', function($sheet) use($contact_array) {
                            $sheet->fromArray($contact_array, null, 'A1', false, false);
                        });
                    })->download('xlsx');
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