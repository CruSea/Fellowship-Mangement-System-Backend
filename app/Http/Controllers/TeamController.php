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
use Carbon\Carbon;

class TeamController extends Controller
{
    public function __construct() {
        $this->middleware('ability:,create-team', ['only' => ['addTeam']]);
        $this->middleware('ability:,get-team', ['only' => ['getTeam', 'getTeams']]);
        $this->middleware('ability:,delete-team', ['only' => ['deleteTeam', 'deleteMember']]);
        $this->middleware('ability:,edit-team', ['only' => ['updateTeam']]);
        $this->middleware('ability:,manage-members', ['only' => ['updateMemberTeam', 'assignMembers', 'seeMembers', 'addMember']]);
    }
    public function addTeam() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            
            if(!$user) {
                return response()->json(['error' => 'token expired'], 404);
            }
            $team = new Team();
            
            $request = request()->only('name', 'description');
            $rule = [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['error' => 'validation error', 'message' => $validator->messages()], 400);
            }
            
            $fellowship_id = $user->fellowship_id;
            
            $fellowship_team = Team::where([['fellowship_id', '=', $fellowship_id], ['name', '=', $request['name']]])->exists();
            if($fellowship_team) {
                return response()->json(['error' => 'team name has already been taken'], 400);
            }

            $team->name = $request['name'];
            $team->description = $request['description'];
            $team->fellowship_id = $fellowship_id;
            $team->created_by = json_encode($user);
            if($team->save()) {
                return response()->json(['message' => 'team added successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => 'something went wrong, team is not saved'], 500);
        } catch(Exception $ex) {
            return response()->json(['message', 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeam($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $team = Team::find($id);
            if($team instanceof Team && $team->fellowship_id == $user->fellowship_id) {
                $team->created_by = json_decode($team->created_by);
            return response()->json(['team' => $team], 200);
                
            } else {
                return response()->json(['message' => 'Ooops! an error occurred', 'error' => 'team is not found'], 404);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeams() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $teams = Team::where('fellowship_id', '=', $user->fellowship_id)->orderBy('id', 'desc')->paginate(10);
            $countTeams = $teams->count();
            if($countTeams == 0) {
                return response()->json(['teams' => $teams], 200);
            }
            for($i = 0; $i < $countTeams; $i++) {
                $teams[$i]->created_by = json_decode($teams[$i]->created_by);
            }
            return response()->json(['teams' => $teams], 200);
        }catch(Exception $ex) {
            return repsonse()->josn(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateTeam($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 404);
            }
            $request = request()->only('name', 'description');
            $team = Team::find($id);
            if(!$team || $team->fellowship_id != $user->fellowship_id) {
                return response()->json(['message' => 'Ooops! an error occurred', 'error' => 'team is not found'], 404);
            }
            $rule = [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            // check name duplication
            $check_name_existance = Team::where([['name', '=',$request['name']], ['fellowship_id', '=', $user->fellowship_id]])->exists();
            if($check_name_existance && $request['name'] != $team->name) {
                return response()->json(['message' => 'team name duplication error', 'error' => 'The team has already been taken.'], 400);
            }
            $team->name = $request['name'];
            $team->description = $request['description'];
            if($team->update()) {
                return response()->json(['message' => 'team updated successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => 'team is not updated'], 500);
        } catch(Exception $ex) { 
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteTeam($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $team = Team::find($id);
            if($team instanceof Team && $team->fellowship_id == $user->fellowship_id) {
                
                if($team->delete()) {
                    return response()->json(['message' => 'team deleted successfully'], 200);
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'team is not deleted'], 500);
                }
            } else {
                return response()->json(['message' => 'error occurred', 'error' => 'team is not found'], 404);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function searchTeam() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $search = Input::get('search');
                if($search) {
                    $teams = Team::where([['name', 'LIKE', '%'.$search.'%'], ['fellowship_id', '=', $user->fellowship_id]])->get();
                    if(count($teams) > 0) {
                        for($i = 0; $i < count($teams); $i++) {
                            $teams[$i]->created_by = json_decode($teams[$i]->created_by);
                        }
                        return $teams;
                    }
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function assignMembers($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('sent_to');
            $rule = [
                'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
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
            $contact = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$contact) {
                return response()->json(['error' => 'contact is not found'], 404);
            }
            $contactTeam = new ContactTeam();
            $team = Team::where([['name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$team) {
                return response()->json(['message' => '404 error', 'error' => 'team is not found'], 404);
            }
            if(!$contactTeam) {
                return response()->json(['error' => 'something went wrong'], 404);
            }
            $team_id = $team->id;
            $contact_id = $contact->id;
            // check contact existance in the team before
            $contactExists = DB::table('contact_teams')->where('contact_id', $contact->id)->first();
            $contactDuplicationInOneTeam = DB::table('contact_teams')->where([
                ['team_id', '=', $team_id],
                ['contact_id', '=', $contact_id],
            ])->get();
            if(count($contactDuplicationInOneTeam) > 0) {
                return response()->json(['message' => 'contact is already found in '. $team->name .' team'], 403);
            }
            $contactTeam->team_id = $team->id;
            $contactTeam->contact_id = $contact->id;
            if($contactTeam->save()) {
                return response()->json(['message' => 'contact assigned '. $team->name.' team successfully'], 200);
            }
            return response()->json(['error' => 'Ooops! something went wrong'], 500);
        } catch(Exception $ex) {
            return ersponse()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function addMember(Request $request, $name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            $contactTeam = new ContactTeam();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
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
            $team = Team::where([['name', '=', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
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
            // check whether contact is found in fellowship
            $check_phone_exists_in_fellowship = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id]])->exists();
            if($check_phone_exists_in_fellowship) {
                $get_contact = Contact::where('phone', '=', $phone_number)->first();
                $under_graduate_contact = Contact::where([['phone', '=', $phone_number], ['is_under_graduate', '=', true]])->first();
                if(!$under_graduate_contact) {
                    return response()->json(['message' => 'unable to add post graduate member','error' => 'post graduate contact '.$get_contact->full_name.' already found by '. $phone_number.' phone number'], 400);
                }
                $team_id = $team->id;
                $contact_id = $under_graduate_contact->id;
                $contactDuplicationInOneTeam = DB::table('contact_teams')->where([
                    ['team_id', '=', $team_id],
                    ['contact_id', '=', $contact_id],
                ])->get();
                if(count($contactDuplicationInOneTeam) > 0) {
                    return response()->json(['error' => 'duplication error', 'message' => 'contact is already found in '. $team->name .' team'], 403);
                } else {
                    $contactTeam->team_id = $team_id;
                    $contactTeam->contact_id = $contact_id;
                    if($contactTeam->save()) {
                        return response()->json(['message' => 'under graduate contacte assigned team successfully'], 200);
                    }
                    return response()->json(['message' => 'an error occured', 'error' => "contact doesn't assigned a team, please try again"], 500);
                }
            }
            // check whether the phone exists before
            $check_phone_existance = Contact::where('phone', $phone_number)->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'The phone has already been taken'], 400);
            }
            // check whethe contact is under graduate
            $this_year_gc = false;
            $graduationYear = $request['graduation_year'].'-07-30';
            $parse_graduation_year = Carbon::parse($graduationYear);
            $today = Carbon::parse(date('Y-m-d'));
            $difference = $today->diffInDays($parse_graduation_year, false);
            
            if($difference <= 0) {
                return response()->json(['error' => 'graduation year is not valid for under graduate member'], 400);
            } else if($difference < 380 && $difference > 0) {
                $this_year_gc = true;
            }
                 
            $contact = new Contact();
            $contact->full_name = $request->input('full_name');
            $contact->gender = $request->input('gender');
            $contact->phone = $phone_number;
            $contact->email = $request['email'];
            $contact->acadamic_department = $request->input('acadamic_department');
            $contact->graduation_year = $request['graduation_year'].'-07-30';
            $contact->is_under_graduate = true;
            $contact->is_this_year_gc = $this_year_gc;
            $contact->fellowship_id = $user->fellowship_id;
            $contact->created_by = $user->full_name;

            if($contact->save()) {
                $team_id = $team->id;
                $contact_id = $contact->id;
                $contactExists = DB::table('contact_teams')->where('contact_id', $contact->id)->first();
                $contactDuplicationInOneTeam = DB::table('contact_teams')->where([
                    ['team_id', '=', $team_id],
                    ['contact_id', '=', $contact_id],
                ])->get();
                if(count($contactDuplicationInOneTeam) > 0) {
                    return response()->json(['error' => 'duplication error', 'message' => 'contact is already found in '. $team->name .' team'], 403);
                }
                $contactTeam->team_id = $team_id;
                $contactTeam->contact_id = $contact_id;
                if($contactTeam->save()) {
                    return response()->json(['message' => 'contacte assigned team successfully'], 200);
                }
                $contact->delete();
                return response()->json(['message' => 'an error occured', 'error' => "contact doesn't assigned a team, please try again"], 500);
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact, please try again'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong']);
        }
    }
    public function seeMembers($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $team = Team::where([['name', '=',$name], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$team) { 
                return response()->json(['message' => 'an error occurred', 'error' => 'team is not found'], 500);
            }
            $team_id = $team->id;
            $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                $team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 1)->orderBy('id', 'desc')->paginate(10);
            if (!$contacts) {
                return response()->json(['message' => 'something went wrong', 'error' => 'contact is not found'], 404);
            }

            $count = $contacts->count();
            if($count == 0) {
                return response()->json(['contacts' => $contacts], 200);
            }
            return response()->json(['contacts' => $contacts], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateMemberTeam($name, $id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('team');
            $contact = Contact::find($id);
            if(!$contact || $contact->fellowship_id != $user->fellowship_id) {
                return response()->json(['error' => 'contact is not found'], 404);
            }
            $rule = [
                'team' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            $getTeam = Team::where([['name', '=' , $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
           if(!$getTeam) {
               return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
           }
            $team = DB::table('teams')->where([['name', '=', $request['team']], ['fellowship_id', '=', $user->fellowship_id]])->first();
            if(!$team) {
                return response()->json(['message' => '404 error', 'error' => 'team is not found'], 404);
            }
            $contact_team = DB::table('contact_teams')->where([['team_id', '=', $getTeam->id], ['contact_id', '=', $contact->id],])->first();
            if(!$contact_team) {
                return response()->json(['message' => 'error found', 'error' => 'contact is not in the team'], 404);
            }
            $get_contact_team = ContactTeam::find($contact_team->id);
            $get_contact_team->team_id = $team->id;

            // check whether user is trying to update to the same team
            if($request['team'] == $name) {
                return response()->json(['message' => 'trying to update to the same team', 'error' => "contact can't be updated to the same team"], 400);
            }
            // check contact is already found in new team
            $check_team_contact_existance = ContactTeam::where([['team_id', $team->id], ['contact_id', $contact->id]])->first();
            if($check_team_contact_existance instanceof ContactTeam) {
                $get_contact_team->delete();
                return response()->json(['response' => 'contact is removed from '.$getTeam->name .' team', 'message' => 'contact is already found in the '. $team->name .' team'], 400);
            }
            if($get_contact_team->update()) {
                return response()->json(['message' => 'contact updated to '. $team->name .' team successfully'], 200);
           }
            
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'contact is not updated'], 500);

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
            if(!$is_under_graduate) {
                return response()->json(['message' => 'this member is not under graduate'], 404);
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
    public function importContactForTeam($name) {
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
                            // check whethe contact is under graduate
                            $this_year_gc = false;
                            $graduationYear = $value->graduation_year.'-07-30';
                            $parse_graduation_year = Carbon::parse($graduationYear);
                            $today = Carbon::parse(date('Y-m-d'));
                            $difference = $today->diffInDays($parse_graduation_year, false);
                            
                            if($difference <= 0) {
                                if($count_add_contacts > 0) {
                                    return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => 'graduation year is not valid for under graduate member'], 400);
                                }
                                return response()->json(['error' => 'graduation year is not valid for under graduate member'], 400);
                            } else if($difference < 380 && $difference > 0) {
                                $this_year_gc = true;
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
                                $contact->is_under_graduate = true;
                                $contact->is_this_year_gc = $this_year_gc;
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
                                $fellowship_contact = Contact::where([['phone', '=', $phone_number], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', true]])->exists();
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
                            return response()->json(['message' => 'member is not added to '.$team->name.' team'], 404);
                            // dd('member is not added to '.$team->name.' team');
                        }
                        return response()->json(['message' => $count_add_contacts.' contacts added to '.$team->name.' team successfully'], 200);
                        // dd($count_add_contacts.' contacts added to '.$team->name.' team successfully');
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
    public function exportTeamContact($name) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team = Team::where([['name', $name], ['fellowship_id', '=', $user->fellowship_id]])->first();
                if($team instanceof Team) { 
                    $team_id = $team->id;
                    $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                        $team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 1)->get()->toArray();
                    if(count($contacts) == 0) {
                        return response()->json(['message' => 'under graduate member is not found in '.$team->name.' team'], 404);
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

                } else {
                    return response()->json(['message' => 'an error occurred', 'error' => 'team is not found'], 500);
                }
            } else {
                json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
