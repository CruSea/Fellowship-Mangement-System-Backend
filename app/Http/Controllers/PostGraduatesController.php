<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use App\Contact;
use App\Team;
use App\ContactTeam;
use App\Fellowship;
use Input;
use JWTAuth;
use Excel;
use DateTime;

class PostGraduatesController extends Controller
{
    public function addPostGraduateContact() {
        try{
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('full_name', 'gender', 'phone', 'email', 'acadamic_department', 'team', 'graduation_year');
            $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                'team' => 'string|min:1|nullable',
                'email' => 'email|max:255|unique:contacts|nullable',
                'graduation_year' => 'required|string',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 500);
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
            $check_phone_existance = Contact::where('phone', $phone_number)->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'The phone has already been taken'], 400);
            }
            // check whether contact is post graduate
            if($request['graduation_year'] > date('Y')) {
                return response()->json(['error' => 'graduation year is not valid for post graduate member'], 400);
            }
            if($request['graduation_year'] == date('Y') && date('m') < 8) {
                return response()->json(['error' => 'graduation year is not valid', 'message' => 'member considered to be post graduate after the month of July'], 400);
            }


            $postGraduate = new Contact();
            $postGraduate->full_name = $request['full_name'];
            $postGraduate->gender = $request['gender'];
            $postGraduate->phone = $phone_number;
            $postGraduate->email = $request['email'];
            $postGraduate->acadamic_department = $request['acadamic_department'];
            $postGraduate->graduation_year = $request['graduation_year'].'-07-30';
            $postGraduate->fellowship_id = $user->fellowship_id;
            $postGraduate->is_under_graduate = false;
            $postGraduate->is_this_year_gc = false;
            $postGraduate->created_by = $user->full_name;
            $team = Team::where([['name', '=', $request['team']], ['fellowship_id', '=', $user->fellowship_id]])->first();

            if($request['team'] != null && !$team) {
                return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
            }

            if($postGraduate->save()) {
                // if($contact->team_id != null) {
                $contact_team = new ContactTeam();
                $contact_team->team_id = $team->id;
                $contact_team->contact_id = $postGraduate->id;
                $contact_team->save();
                // }
                return response()->json(['message' => 'contact added successfully'], 200);
                
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact'], 500);
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function show($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if(!$user) {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    		$postGraduate = Contact::find($id);
    		
    		if($postGraduate instanceof Contact && $postGraduate->fellowship_id == $user->fellowship_id) {
    			$is_post_graduate = $postGraduate->is_under_graduate;
    			if($is_post_graduate == 1) {
    				return response()->json(['message' => 'contact is under graduate'], 404);
    			}
    			return response()->json(['post_graduate' => $postGraduate], 200);
    		}
    		return response()->json(['message' => 'post graduate is not available', 'error' => 'unable to find post graduate'], 404);
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function getPostGraduates() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if(!$user) {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    		// $postGraduates = Contact::all();
    		$postGradautes = Contact::where([['is_under_graduate', '=', 0], ['fellowship_id', '=', $user->fellowship_id]])->orderBy('id', 'desc')->paginate(10);
            $count = count($postGradautes);
            if($count == 0) {
                return response()->json(['post_graduates' => $postGradautes], 200);
            }

    		return response()->json(['post_graduates' => $postGradautes], 200);
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function update($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if(!$user) {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    		$request = request()->only('full_name', 'gender', 'phone', 'email', 'team', 'acadamic_department', 'graduation_year');
	    	$rule = [
	    		'full_name' => 'string|max:255',
                'gender' => 'string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                'email' => 'email|max:255|nullable',
                'graduation_year' => 'string',
	    	];
	    	$validator = Validator::make($request, $rule);
	    	if($validator->fails()) {
	    		return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
	    	}
	    	$postGradaute = Contact::find($id);
	    	if($postGradaute instanceof Contact && $postGradaute->fellowship_id == $user->fellowship_id) {
	    		$is_under_graduate = $postGradaute->is_under_graduate;
	    		if($is_under_graduate == 1) {
	    			return response()->json(['message' => 'contact is under graduate'], 404);
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
                // check whether contact is post graduate
                if($request['graduation_year'] > date('Y')) {
                    return response()->json(['error' => 'graduation year is not valid for post graduate member'], 400);
                }
                if($request['graduation_year'] == date('Y') && date('m') < 8) {
                    return response()->json(['error' => 'graduation year is not valid', 'message' => 'member considered to be post graduate after the month of July'], 400);
                }
	    		// check weather the phone exists before
                $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                if($check_phone_existance && $phone_number != $postGradaute->phone) {
                    return response()->json(['error' => 'The phone has already been taken'], 400);
                }
                $postGradaute->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $postGradaute->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $postGradaute->phone = isset($request['phone']) ? $phone_number : $contact->phone;
                $postGradaute->email = isset($request['email']) ? $request['email'] : $contact->email;
                $postGradaute->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                $postGradaute->graduation_year = isset($request['graduation_year']) ? $request['graduation_year'].'-07-30' : $postGradaute->graduation_year;
	    		if($postGradaute->update()) {
	    			return response()->json(['message' => 'post graduate updated successfully'], 200);
	    		}
	    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'post graduate is not updated'], 500);
	    	}
	    	return response()->json(['message' => 'contact is not found', 'error' => 'unable to find post graduate'], 404);
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function delete($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if(!$user) {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    		$postGraduate = Contact::find($id);
    		if($postGraduate instanceof Contact && $postGraduate->fellowship_id == $user->fellowship_id) {
    			$is_post_graduate = $postGraduate->is_under_graduate;
    			if($is_post_graduate == 1) {
    				return response()->json(['message' => 'contact is under graduate', 'error' => "under graduate contact can't be deleted here"], 400);
    			}
    			if($postGraduate->delete()) {
    				return response()->json(['message' => 'postGradaute deleted successfully'], 200);
    			}
    			return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'post graduate is not deleted'], 500);
    		}
    		return response()->json(['message' => 'post graduate is not found', 'error' => 'unable to find post graduate'], 404);
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function searchPostGraduate() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                // $contacts = Contact::query();
                $search = Input::get('search');
                if($search) {
                    $contacts = Contact::where([['full_name', 'LIKE', '%'.$search.'%'], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', false]])->orWhere([['phone', 'LIKE','%'.$search.'%'], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', true]])->get();
                    if(count($contacts) > 0) {
                        return $contacts;
                    }
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function importPostGraduateContact() { 
        $user = JWTAuth::parseToken()->toUser();
        if(!$user) {
            return response()->json(['error' => 'token expired'], 401);
        }
        $count_add_contacts = 0;
        if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
            $data = Excel::load($path, function($reader) {
            })->get();
            $headerRow = $data->first()->keys();
            $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3], $headerRow[4], $headerRow[5], $headerRow[6]);
            if(!empty($data) && $data->count()){
                foreach ($data as $key => $value) {
                    // phone validation 
                    if($value->phone == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => "validation error", 'error' => "phone can't be null"], 403);
                        }
                        dd('validation error phone can not be null');
                        return response()->json(['message' => "validation error", 'error' => "phone can't be null"], 403);
                    }
                    if($value->full_name == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "full name can't be null"], 403);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "full name can't be null"], 403);
                    }
                    if($value->gender == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "gender can't be null"], 403);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "gender can't be null"], 403);
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
                    // check whether contact is post graduate
                    if($value->graduation_year > date('Y')) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => 'graduation year is not valid for post graduate member'], 400);
                        }
                        return response()->json(['error' => 'graduation year is not valid for post graduate member'], 400);
                    }
                    if($value->graduation_year == date('Y') && date('m') < 8) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => 'graduation year is not valid', 'message' => 'member considered to be post graduate after the month of July'], 400);
                        }
                        return response()->json(['error' => 'graduation year is not valid', 'message' => 'member considered to be post graduate after the month of July'], 400);
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
                    // check weather the phone exists before
                    $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                    // check weather the email exists before
                    $check_email_existance = Contact::where([['email', '=',$value->email],['email', '!=', null]])->exists();
                    if(!$check_phone_existance && !$check_email_existance && strlen($phone_number) == 13) {
                        $contact = new Contact();
                        $contact->full_name = $value->full_name;
                        $contact->gender = $value->gender;
                        $contact->phone = $phone_number;
                        $contact->email = $value->email;
                        $contact->acadamic_department = $value->acadamic_department;
                        $contact->graduation_year = $value->graduation_year.'-07-30';
                        $contact->fellowship_id = $user->fellowship_id;
                        $contact->is_under_graduate = false;
                        $contact->is_this_year_gc = false;
                        $contact->created_by = $user->full_name;
                        if($contact->save()) {
                            if($value->team != null && $team instanceof Team) {
                                $contact_team = new ContactTeam();
                                $contact_team->team_id = $team->id;
                                $contact_team->contact_id = $contact->id;
                                $contact_team->save();
                            }
                            $count_add_contacts++;
                        }
                    }
                }
                if($count_add_contacts == 0) {
                    return response()->json(['message' => 'no contact is added'], 404);
                }
                return response()->json(['message' => $count_add_contacts.' contacts added successfully'], 200);
            }
            else {
                return response()->json(['message' => 'file is empty', 'error' => 'No contact is found in the file'], 404);
            }
        }
        return response()->json(['message' => 'File not found', 'error' => 'Contact File is not provided'], 404);
    }
    public function exportPostGraduateContact() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $contacts = Contact::where([['is_under_graduate', '=', 0], ['fellowship_id', '=', $user->fellowship_id]])->get()->toArray();
                if(count($contacts) == 0) {
                    return response()->json(['message' => 'post graduate member is not found'], 404);
                }
                $contact_array = array('full_name','gender', 'phone', 'email', 'acadamic_department', 'graduation_year', 'created_by', 'created_at', 'updated_at');
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
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
