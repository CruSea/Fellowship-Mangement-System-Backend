<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Contact;
use App\Team;
use App\ContactTeam;
use App\Fellowship;
use JWTAuth;

class PostGraduatesController extends Controller
{
    public function store() {
    	try{
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to add contacts"], 401);
            }
            $request = request()->only('full_name', 'gender', 'phone', 'email', 'acadamic_department', 'team', 'graduation_year');
            $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                'email' => 'required|email|max:255|unique:contacts',
                'graduation_year' => 'required|string',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 500);
            }
            $postGradaute = new Contact();
            $postGradaute->full_name = $request['full_name'];
            $postGradaute->gender = $request['gender'];
            $postGradaute->phone = $request['phone'];
            $postGradaute->email = $request['email'];
            $postGradaute->acadamic_department = $request['acadamic_department'];
            $postGradaute->graduation_year = $request['graduation_year'].'-07-30';
            $postGradaute->fellowship_id = $user->fellowship_id;
            // $postGraduate->is_under_graduate = 0;
            $postGradaute->created_by = json_encode($user);
            $team = Team::where('name', '=', $request['team'])->first();

            if($request['team'] != null && !$team) {
                return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
            }

            if($postGradaute->save()) {
                $contact_team = new ContactTeam();
                $contact_team->team_id = $team->id;
                $contact_team->contact_id = $postGradaute->id;
                $contact_team->save();
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
    			return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
    		}
    		$postGraduate = Contact::find($id);
    		
    		if($postGraduate instanceof Contact) {
    			$is_post_graduate = $postGraduate->is_under_graduate;
    			if($is_post_graduate == 1) {
    				return response()->json(['message' => 'contact is under graduate'], 404);
    			}
    			return response()->json(['post graduate' => $postGraduate], 200);
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
    			return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
    		}
    		// $postGraduates = Contact::all();
    		$postGradautes = Contact::where('is_under_graduate', '=', 0)->get();
    		return response()->json(['post graduates' => $postGradautes], 200);
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    	}
    }
    public function update($id) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if(!$user) {
    			return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
    		}
    		$request = request()->only('full_name', 'gender', 'phone', 'email', 'team', 'acadamic_department', 'graduated_year');
	    	$rule = [
	    		'full_name' => 'string|max:255',
                'gender' => 'string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                'email' => 'email|max:255',
                'graduation_year' => 'string',
	    	];
	    	$validator = Validator::make($request, $rule);
	    	if($validator->fails()) {
	    		return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
	    	}
	    	$postGradaute = Contact::find($id);
	    	if($postGradaute instanceof Contact) {
	    		$is_under_graduate = $postGradaute->is_under_graduate;
	    		if($is_under_graduate == 1) {
	    			return response()->json(['message' => 'contact is under graduate'], 404);
	    		}
	    		// check weather the phone exists before
                $check_phone_existance = DB::table('contacts')->where('phone', $request['phone'])->exists();
                if($check_phone_existance && $request['phone'] != $postGradaute->phone) {
                    return response()->json(['error' => 'The phone has already been taken'], 400);
                }
                $postGradaute->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $postGradaute->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $postGradaute->phone = isset($request['phone']) ? $request['phone'] : $contact->phone;
                $postGradaute->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                $postGradaute->graduation_year = isset($request['graduation_year']) ? $request['graduation_year'] : $postGraduate->graduation_year;
                $postGraduate->team = isset($request['team']) ? $request['team'] : $postGraduate->team;

	    		// $postGraduates->full_name = isset($request['full_name']) ? $request['full_name'] : $postGradaute->full_name;
	    		// $postGradaute->gender = isset($request['gender']) ? $request['gender'] : $postGradaute->gender;
	    		// $postGradaute->phone = isset($request['phone']) ? $request['phone'] : $postGradaute->phone;
	    		// $postGradaute->email = isset($request['email']) ? $request['email'] : $postGradaute->email;
	    		// $postGraduate->team = isset($request['team']) ? $request['team'] : $postGraduate->team;
	    		// $postGraduate->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $postGraduate->acadamic_department;
	    		// $postGraduate->graduation_year = isset($request['graduation_year']) ? $request['graduation_year'] : $postGraduate->graduation_year;
	    		if($postGraduate->update()) {
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
    			return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
    		}
    		$postGraduate = Contact::find($id);
    		if($postGraduate instanceof Contact) {
    			$is_post_graduate = $postGraduate->is_under_graduate;
    			if($is_post_graduate == 1) {
    				return response()->json(['message' => 'contact is under graduate', "error' => 'under graduate contact can't be deleted here"], 400);
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
}
