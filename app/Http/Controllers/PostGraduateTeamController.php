<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Team;
use App\User;
use App\Contact;
use App\ContactTeam;
use App\Fellowship;
use Input;
use Excel;
use JWTAuth;
use DateTime;
class PostGraduateTeamController extends Controller
{
    public function addPostGraduateMember($name) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
            $postGraduateTeam = new ContactTeam();
            $team = Team::where([['name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$team) {
                return response()->json(['error' => 'team is not found'], 404);
            }
    		if($user instanceof User) {
    			$request = request()->only('full_name', 'phone', 'gender', 'email', 'acadamic_department', 'graduation_year');
                $rule = [
                    'full_name' => 'required|string|max:255',
                    'gender' => 'required|string|max:255',
                    'acadamic_department' => 'string|max:255',
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                    'email' => 'email|max:255|unique:contacts|nullable',
                    'graduation_year' => 'required|string',
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
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
                // check whether the contact exists in the same fellowship
                $check_phone_exists_in_fellowship = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->exists();
                if($check_phone_exists_in_fellowship) {
                    $get_contact = Contact::where('phone', '=', $phone_number)->first();
                    // check whether contact is post graduate
                    $postgraduate = Contact::where([['phone', '=', $phone_number], ['is_under_graduate', '=', 0]])->first();
                    if(!$postgraduate) {
                        return response()->json(['message' => 'unable to add under graduate member','error' => 'under graduate contact '. $get_contact->full_name.' is already found by '. $phone_number.' phone number'], 400);
                    }
                    $team_id = $team->id;
                    $post_graduate_id = $postgraduate->id;
                    $postGraduateDuplicationInOneTeam = ContactTeam::where([['team_id', '=',$team_id],['contact_id', '=', $post_graduate_id],])->get();
                    if(count($postGraduateDuplicationInOneTeam) > 0) {
                        return response()->json(['error' => 'duplication error', 'message' => 'post graduate is already found in '. $team->name. ' team'], 400);
                    } else {
                        $postGraduateTeam->team_id = $team_id;
                        $postGraduateTeam->contact_id = $post_graduate_id;
                        if($postGraduateTeam->save()) {
                            return response()->json(['message' => 'post graduate contact added to '. $team->name.' team successfully'], 200);
                        }
                        return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'post graduate is not added successfully, please try again'], 500);
                    }
                }
                // check whether the phone exists before
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
                $postGraduate->is_under_graduate = false;
                $postGraduate->is_this_year_gc = false;
                $postGraduate->fellowship_id = $user->fellowship_id;
                $postGraduate->created_by = $user->full_name;

                if($postGraduate->save()) {
                    $team_id = $team->id;
                    $postGraduate_id = $postGraduate->id;
                    $postGraduateDuplicationInOneTeam = ContactTeam::where([['team_id', '=',$team_id],['contact_id', '=', $postGraduate_id],])->get();
                    if(count($postGraduateDuplicationInOneTeam) > 0) {
                        return response()->json(['error' => 'duplication error', 'message' => 'post graduate is already found in '. $team->name. ' team'], 400);
                    }
                    $postGraduateTeam->team_id = $team_id;
                    $postGraduateTeam->contact_id = $postGraduate_id;
                    if($postGraduateTeam->save()) {
                        return response()->json(['message' => 'post graduate contact added to '. $team->name.' team successfully'], 200);
                    }
                    $postGraduate->delete();
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'post graduate is not added successfully, please try again'], 500);
                }
                return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'post graduate is not added successfully, please try again'], 500);
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function seeMembers($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team = Team::where([['name', '=',$name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if(!$team) { 
                    return response()->json(['message' => 'an error occurred', 'error' => 'team is not found'], 500);
                }
                $team_id = $team->id;
                $postGradautes = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                $team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 0)->orderBy('id', 'desc')->paginate(10);
                if (!$postGradautes) {
                    return response()->json(['message' => 'something went wrong', 'error' => 'contact is not found'], 404);
                }

                $count = count($postGradautes);
                
                if($count == 0) {
                    return response()->json(['contacts' => $postGradautes], 200);
                }
                return response()->json(['contacts' => $postGradautes], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function importPostGraduateContactForTeam($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team = Team::where([['name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if(!$team) {
                    return response()->json(['error' => 'team is not found'], 404);
                }
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
                                    $contact_team = new ContactTeam();
                                    $contact_team->team_id = $team->id;
                                    $contact_team->contact_id = $contact->id;
                                    $contact_team->save();
                                    $count_add_contacts++;
                                }
                            }
                            if($check_phone_existance) {
                                // check whether contact is found in the same fellowship (with the admin)
                                $fellowship_contact = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', false]])->exists();
                                if($fellowship_contact) {
                                    $contact = Contact::where('phone', '=', $phone_number)->first();
                                    $contact_team = new ContactTeam();
                                    $check_member_existance = ContactTeam::where([['team_id', '=', $team->id],['contact_id', '=', $contact->id]])->first();
                                    if(!$check_member_existance) {
                                        $contact_team->team_id = $team->id;
                                        $contact_team->contact_id = $contact->id;
                                        $contact_team->save();
                                        $count_add_contacts++;
                                    }
                                }
                                
                            }
                        }
                        if($count_add_contacts == 0) {
                            return response()->json(['message' => 'member is not added to '. $teram->name. ' team'], 200);
                        }
                        return response()->json(['message' => $count_add_contacts.' contacts added to '. $team->name.' team successfully'], 200);
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
    public function exportPostGraduateTeamContact($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team = Team::where([['name', '=',$name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if($team instanceof Team) { 
                    $team_id = $team->id;
                    $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                        $team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 0)->get()->toArray();
                    if(count($contacts) == 0) {
                        return response()->json(['message' => 'post graduate member is not found in '.$team->name.' team'], 404);
                    }
                    $contact_array[] = array('full_name','gender', 'phone', 'email', 'acadamic_department', 'graduation_year', 'created_by', 'created_at', 'updated_at');
                    foreach ($contacts as $contact) {
                        $contact_array[] = array(
                            'full_name' => $contact->full_name,
                            'gender' => $contact->gender,
                            'email' => $contact->email,
                            'phone' => $contact->phone,
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
                }
            } else {
                json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteMember($name, $id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $contact = Contact::find($id);
            if(!$contact || $contact->fellowship_id != $user->fellowship_id) {
                return response()->json(['message' => '404 error', 'error' => 'contact is not found'], 404);
            }
            $is_under_graduate = $contact->is_under_graduate;
            if($is_under_graduate) {
                return response()->json(['message' => 'this member is not post graduate'], 404);
            }
           $team = Team::where([['name', '=' , $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
           if(!$team) {
               return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
           }
           $is_contact_in_team = DB::table('contact_teams')->where([['team_id', '=', $team->id], ['contact_id', '=', $contact->id],])->first();
           if(!$is_contact_in_team) {
               return response()->json(['message' => 'error found', 'error' => 'contact is not found in the team'], 404);
           }
           $contact_team = DB::table('contact_teams')->where([['team_id', '=', $team->id], ['contact_id', '=', $contact->id],]);
    
           if($contact_team->delete()) {
               return response()->json(['message' => 'contact deleted from the team successfully'], 200);
           }
           return response()->json(['message' => 'unexpected error', 'error' => "Ooops! contact doesn't deleted from the team successfully"], 500);
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
