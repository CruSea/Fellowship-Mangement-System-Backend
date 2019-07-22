<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Team;
use App\User;
use App\Contact;
use App\ContactTeam;
use App\Fellowship;
use Input;
use Excel;
use JWTAuth;
use DateTime;

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
            
            $team = new Team();
            
            $request = request()->only('name', 'description');
            $rule = [
                'name' => 'required|string|max:255|unique:teams',
                'description' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => 'user is not autorized to add a team'], 404);
            }

            $fellowship_id = $user->fellowship_id;
            
            $team->name = $request['name'];
            $team->description = $request['description'];
            $team->fellowship_id = $fellowship_id;
            $team->created_by = json_encode($user);
            if($team->save()) {
                return response()->json(['message' => 'team added successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => 'team is not saved'], 500);
        } catch(Exception $ex) {
            return response()->json(['message', 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeam($id) {
        try {
            if(!$team = Team::find($id)) {
                return response()->json(['message' => 'Ooops! an error occurred', 'error' => 'team is not found'], 404);
            }
            $team->created_by = json_decode($team->created_by);
            return response()->json(['team' => $team], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeams() {
        try {
            $teams = Team::paginate(10);
            $countTeams = Team::count();
            if($countTeams == 0) {
                return response()->json(['error' => 'team is not empty'], 404);
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
                return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to update team'], 404);
            }
            $request = request()->only('name', 'description');
            $team = Team::find($id);
            if(!$team) {
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
            $check_name_existance = Team::where('name', '=',$request['name'])->exists();
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
            if(!$team = Team::find($id)) {
                return response()->json(['message' => 'error occurred', 'error' => 'team is not found'], 404);
            }
            if($team->delete()) {
                return response()->json(['message' => 'team deleted successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'team is not deleted'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function assignMembers($name) {
        try {
            $request = request()->only('phone');
            $rule = [
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
            }
            $contact = Contact::where('phone', '=', $request['phone'])->first();
            if(!$contact) {
                return response()->json(['error' => 'contact is not found'], 404);
            }
            $contactTeam = new ContactTeam();
            $team = DB::table('teams')->where('name', '=', $name)->first();
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
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                'email' => 'email|max:255|unique:contacts|nullable',
                'graduation_year' => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
            }
            $contactTeam = new ContactTeam();
            $team = DB::table('teams')->where('name', '=', $name)->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
            }
            
            $contact = new Contact();
            $contact->full_name = $request->input('full_name');
            $contact->gender = $request->input('gender');
            $contact->phone = $request->input('phone');
            $contact->email = $request['email'];
            $contact->acadamic_department = $request->input('acadamic_department');
            $contact->graduation_year = $request['graduation_year'].'-07-30';
            $contact->is_under_graduate = true;
            $contact->is_this_year_gc = false;
            $contact->fellowship_id = $user->fellowship_id;
            $contact->created_by = json_encode($user);

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
            $team = DB::table('teams')->where('name', $name)->first();
            if(!$team) { 
                return response()->json(['message' => 'an error occurred', 'error' => 'team is not found'], 500);
            }
            $team_id = $team->id;
            $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
            $team_id)->select('contact_id')->get())->get();
            if (!$contacts) {
                return response()->json(['message' => 'something went wrong', 'error' => 'contact is not found'], 404);
            }

            $count = $contacts->count();
            // $under_graduate;
            if($count == 0) {
                return response()->json(['message' => 'contact is not found'], 404);
            }
            $under_graduate = [];
            for($i = 0; $i < $count; $i++) {
                if($contacts[$i]->is_under_graduate) {
                    $contacts_two = $contacts;
                    $contacts[$i]->created_by = json_decode($contacts[$i]->created_by);
                    $under_graduate[] = Contact::where([['id', $contacts[$i]->id],['is_under_graduate', 1]])->paginate(10);
                    // $under_graduate[$i]->created_by = json_decode($under_graduate[$i]->created_by);
                    // return response()->json(['contacts' => $contacts], 200);
                    
                }

                
            }
            if(!count($under_graduate)) {
                return response()->json(['message' => 'under graduate contact is empty'], 404);
            }
            // return response()->json(['count' => $under_graduate[2]], 200);
            // for($j = 0; $j < 3; $j++) {
            //     $under_graduate[$j]->created_by = json_decode($under_graduate[$j]->created_by);
            // }

            
            return response()->json(['contacts' => $under_graduate], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateMemberTeam($name, $id) {
        try {
            $request = request()->only('team');
            $contact = Contact::find($id);
            if(!$contact) {
                return response()->json(['error' => 'contact is not found'], 404);
            }
            $rule = [
                'team' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            $getTeam = DB::table('teams')->where('name', '=' , $name)->first();
           if(!$getTeam) {
               return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
           }
            $team = DB::table('teams')->where('name', '=', $request['team'])->first();
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
            $contact = Contact::find($id);
            if(!$contact) {
                return response()->json(['message' => '404 error', 'error' => 'contact is not found'], 404);
            }
            $is_under_graduate = $contact->is_under_graduate;
            if(!$is_under_graduate) {
                return response()->json(['message' => 'this member is not under graduate'], 404);
            }
           $team = DB::table('teams')->where('name', '=' , $name)->first();
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
            $team = Team::where('name', '=', $name)->first();
            if(!$team) {
                return response()->json(['error' => 'team is not found'], 404);
            }
            $contactTeam = new ContactTeam();
            if($user instanceof User) {
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
                                return response()->json(['message' => 'validation error', 'error' => "phone can't be null"], 404);
                            }
                            // full_name validation 
                            if($value->full_name == null) {
                                return response()->json(['message' => 'validatoin error', 'error' => "full name can't be null"], 404);
                            }
                            // gender validatin
                            if($value->gender == null) {
                                return response()->json(['message' => 'validation error', 'error' => "gender can't be null"], 404);
                            }
                            if($value->email == null) {
                                return response()->json(['message' => 'validation error', 'error' => "email can't be null"], 404);
                            }
                            if($value->graduation_year == null) {
                                return response()->json(['message' => 'validation error', 'error' => "graduation year can't be null"], 404);
                            }
                            $insert[] = ['full_name' => $value->full_name, 'phone' => $value->phone, 'email' => $request['email'], 'gender' => $value->gender, 'acadamic_department' => $acadamic_department, 'graduation_year' => $request['graduation_year'],'fellowship_id' => $user->fellowship_id, 'created_by' => json_encode($user), 'is_under_graduate' => true,
                                'is_this_year_gc' => false, 'created_at' => new DateTime(), 'updated_at' => new DateTime()];
                        }
                        if(!empty($insert)) {
                            $contact = new Contact();
                            $contact::insert($insert);

                            $team_id = $team->id;
                            $contact_id = $contact->id;
                            $contactTeam->team_id = $team_id;
                            $contactTeam->contact_id = $contact_id;
                            // DB::table('contacts')->insert($insert);
                            dd('Insert recorded successfully.');
                        }
                    }
                    else {
                        return response()->json(['message' => 'file is empty', 'error' => 'unable to add contact'], 404);
                    }
                }
                return response()->json(['message' => 'File not found', 'error' => 'Contact file is not provided'], 404);
            } 
            return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
