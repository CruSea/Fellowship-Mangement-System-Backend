<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Team;
use App\Contact;
use App\ContactTeam;
use App\Fellowship;
use JWTAuth;

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
            return response()->json(['team' => $team], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeams() {
        try {
            $teams = Team::all();
            $countTeams = Team::count();
            if($countTeams == 0) {
                return response()->json(['error' => 'team is not empty'], 404);
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
    public function assignMembers($name, $id) {
        try {
            $contact = Contact::find($id);
            $contactTeam = new ContactTeam();
            $team = DB::table('teams')->where('name', '=', $name)->first();
            if(!$team) {
                return response()->json(['message' => '404 error', 'error' => 'team is not found'], 404);
            }
            if(!$contact) {
                return response()->json(['message' => '404 error', 'error' => 'contact is not found'], 404);
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
                return response()->json(['error' => 'duplication error', 'message' => 'contact is already add to '. $team->name .' team'], 403);
            }
            $contactTeam->team_id = $team->id;
            $contactTeam->contact_id = $contact->id;
            if($contactTeam->save()) {
                return response()->json(['message' => 'contacte assigned team successfully'], 200);
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
            $team = DB::table('teams')->where('name', '=', $name)->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
            }
            if(!$user) {
                return response()->json(['error' => 'user is not authenticated'], 500);
            }
            $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
            ];
            $validator = Validator::make($request->all(), $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
            }
            
            $contact = new Contact();
            $contact->full_name = $request->input('full_name');
            $contact->gender = $request->input('gender');
            $contact->phone = $request->input('phone');
            $contact->acadamic_department = $request->input('acadamic_department');
            $contact->fellowship_id = $user->fellowship_id;

            if($contact->save()) {
                $team_id = $team->id;
                $contact_id = $contact->id;
                $contactExists = DB::table('contact_teams')->where('contact_id', $contact->id)->first();
                $contactDuplicationInOneTeam = DB::table('contact_teams')->where([
                    ['team_id', '=', $team_id],
                    ['contact_id', '=', $contact_id],
                ])->get();
                if(count($contactDuplicationInOneTeam) > 0) {
                    return response()->json(['error' => 'duplication error', 'message' => 'contact is already add to '. $team->name .' team'], 403);
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
            if($count == 0) {
                return response()->json(['message' => 'contact is not found'], 404);
            }
            
            return response()->json(['contacts' => $contacts], 200);
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
}
