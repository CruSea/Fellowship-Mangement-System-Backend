<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Team;
use App\Contact;
use App\ContactTeam;

class TeamController extends Controller
{
    public function __construct() {
        $this->middleware('ability:,create-team', ['only' => ['addTeam']]);
        $this->middleware('ability:,get-team', ['only' => ['getTeam', 'getTeams']]);
        $this->middleware('ability:,delete-team', ['only' => ['deleteTeam']]);
        $this->middleware('ability:,edit-team', ['only' => ['updateTeam']]);
        $this->middleware('ability:,manage-members', ['only' => ['updateUserRole', 'assignTeam', 'seeMembers']]);
    }
    public function addTeam() {
        try {
            $request = request()->only('name', 'description');
            // check name duplication
            $name = DB::table('teams')->where('name', $request['name'])->exists();
            if($name) {
                return response()->json(['message' => 'team name duplication error', 'error' => 'team name is duplicated'], 500);
            }
            $rule = [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'the values are not valid'], 500);
            }
            $team = new Team();
            $team->name = $request['name'];
            $team->description = $request['description'];
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
            $teams = new Team();
            return response()->json(['teams' => $teams->paginate(10)], 200);
        }catch(Exception $ex) {
            return repsonse()->josn(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateTeam($id) {
        try {
            $request = request()->only('name', 'description');
            $team = Team::find($id);
            if(!$team) {
                return response()->json(['message' => 'Ooops! an error occurred', 'error' => 'team is not found'], 404);
            }
            // check name duplication
            $check_name_existance = DB::table('teams')->where('name', $request['name'])->exists();
            if($check_name_existance && $request['name'] != $team->name) {
                return response()->json(['message' => 'team name duplication error', 'error' => 'team name is duplicated'], 500);
            }
            $rule = [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'the values are not valid'], 500);
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
        // try {
        //     if(!$team = Team::find($id)) {
        //         return response()->json(['message' => 'error occurred', 'error' => 'team is not found'], 404);
        //     }
        //     if($team->delete()) {
        //         return response()->json(['message' => 'team deleted successfully'], 200);
        //     }
        //     return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'team is not deleted'], 500);
        // } catch(Exception $ex) {
        //     return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        // }
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
            // check contact existance in the team before
            $contactExists = DB::table('contact_teams')->where('contact_id', $contact->id)->first();
            // is the user trying to add contact to another team
            // $antherTeam = DB::table('contact_teams')->where('team_id', $team->id)
            // if($contactExists) {
            // 
            //     return response()->json(['message' => 'contacte is already added'], 201);
            // }
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
    public function seeMembers($name) {
        // try {
        //     $team = DB::table('teams')->where('name', $name)->first();
        //     if(!$team) { 
        //         return response()->json(['message' => 'an error occurred', 'error' => 'team is not found', 'team' => $team], 500);
        //     }
        //     $teamId = $team->id;
        //     $memebers = DB::table('contacts')->where('team_id', $teamId)->get();
        //     $check_members_existance = DB::table('contacts')->where('team_id', $teamId)->first();
        //     if($check_members_existance) {
        //     return response()->json(['members' => $memebers], 200);
        //     } 
        //     return response()->json(['message' => 'members are not found in '. $name .' team'], 404);
        // } catch(Exception $ex) {
        //     return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        // }
        try {
            $team = DB::table('teams')->where('name', $name)->first();
            if(!$team) { 
                return response()->json(['message' => 'an error occurred', 'error' => 'team is not found', 'team' => $team], 500);
            }
            $contactTeam = new Contact();
            $teamId = $team->id;
            // $getTeam = $contactTeam->$teamId;
            $contacts = DB::table('contact_teams')->where('team_id', $teamId)->get();
            // $members = DB::table('contacts')->where('id', )->get();
            return response()->json(['contacs' => $contacts], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateMembers($name, $id) {
        // try {
        //     $contact = Contact::find($id);
        //     if(!$contact) {
        //         return response()->json(['message' => '404 error', 'error' => 'contact is not found'], 404);
        //     }
        //     $request = request()->only('name');
        //     $rule = [
        //         'name' => 'required|string|max:255',
        //     ];
        //     $team = DB::table('teams')->where('name', '=',  $request['name'])->first();
        //     if(!$team) {
        //         return response()->json(['message' => '404 error', 'error' => 'team is not found'], 404);
        //     }
        //     $contact->team_id = $team->id;
        //     if($contact->update()) {
        //         return response()->json(['message' => 'contact team updated to '. $request['name']. ' team'], 200);
        //     }
        //     return response()->json(['message' => 'unexpected error', 'error' => "contact's team doesn't updated"], 500);
        // } catch(Exception $ex) {
        //     return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        // }
        try {
            $request = request()->only('team');
            $contact = Contact::find($id);
            if(!$contact) {
                return response()->json(['error' => 'contact is not found'], 404);
            }
            $rule = [
                'team' => 'required',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'team is not valid'], 500);
            }
            $team = DB::table('teams')->where('name', '=', $request['team'])->first();
            if(!$team) {
                return response()->json(['message' => '404 error', 'error' => 'team is not found'], 404);
            }
            $contactTeam = new ContactTeam();
            $team_id = DB::table('contact_teams')->where('contact_id', '=', $contact);
            return response()->json(['contactTeam' => $team_id], 200);

        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
     public function deleteMembers($name, $id) {
        // try {
        //     $contact = Contact::find($id);
        //     if(!$contact) {
        //         return response()->json(['message' => '404 error', 'error' => 'contact is not found'], 404);
        //     }
        //    // $team = DB::table('teams')->where('name', '=' , 'praer')->first();
        //     // $contact->team_id = $team->id;
        //     $contact->team_id = null;
            
        //     if($contact->update()) { 
        //         return response()->json(['message' => 'contact deleted from the team successfully'], 200);
        //     }
        //     return response()->json(['message' => 'unexpected error', 'error' => "Ooops! team doesn't assigned seccessfully"], 500);
        // }catch(Exception $ex) {
        //     return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        // }
    }
}
