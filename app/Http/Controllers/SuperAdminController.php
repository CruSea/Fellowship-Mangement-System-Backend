<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Contact;
use App\Team;
use App\ContactTeam;
use App\Fellowship;
use Input;
use JWTAuth;
use DateTime;

class SuperAdminController extends Controller
{
	public function fellowship_detail() {
		try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$count_fellowship = Fellowship::count();
	    		$list_fellowship = Fellowship::paginate(10);
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	            return response()->json(['error' => 'Ooops! something went wrong', 'message' => $ex->getMessage()], 500);
        }
	}
	public function users_detail() {
		try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$users = User::orderBy('id', 'desc')->paginate(10);
	    		foreach ($users as $user) {
	    			$fellowship = Fellowship::find($user->fellowship_id);
	    			$user->fellowship_name = $fellowship->university_name;
	    		}
	    		return response()->json(['users' => $users], 200);
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	            return response()->json(['error' => 'Ooops! something went wrong', 'message' => $ex->getMessage()], 500);
        }
	}
	public function teams_detail() {
		try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$team_array = array();

	    		$fellowship = Fellowship::all();
	    		// return response()->json(['fellowship_id' => $fellowship], 200);
	    		foreach ($fellowship as $fellowship) {
	    			$team = Team::where('fellowship_id', '=', $fellowship->id)->get();
	    			$count_team = $team->count();
	    			$fellowship_name = $fellowship->university_name;
	    		}
	    		return response()->json(['count_team' => $count_team, 'fellowship' => $fellowship_name], 200);
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	            return response()->json(['error' => 'Ooops! something went wrong', 'message' => $ex->getMessage()], 500);
        }
	}
	public function contacts_detail() {
		try {
	    	$user = JWTAuth::parseToken()->toUser();
	    	if($user instanceof User) {
	    		$fellowships = Fellowship::all();
	    		$contact_array = array();
	    		$contacts = Contact::count();
	    		// foreach ($fellowships as $fellowship) {
	    		// 	array_push($contact_array, $fellowship->id);
	    		// 	$count_contact = Contact::where('fellowship', '=', $fellowship->id)->count();
	    		// 	$fellowship = Fellowship::find($contact->fellowship_id);
	    		// 	$fellowship_contacts_number = Contact::where('fellowship', '=', $fellowship->id)->count();

	    		// }
	    		$contact = Contact::whereIn('id', $contact_array)->get();
	    	} else {
	    		return response()->json(['error' => 'token expired'], 401);
	    	}
	    } catch(Exception $ex) {
	            return response()->json(['error' => 'Ooops! something went wrong', 'message' => $ex->getMessage()], 500);
        }
	}
    
}
