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
                $postGraduate->created_by = json_decode($postGraduate->created_by);
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
    			return response()->json(['error' => 'token expired'], 401);
    		}
    		// $postGraduates = Contact::all();
    		$postGradautes = Contact::where('is_under_graduate', '=', 0)->get();
            $count = count($postGradautes);
            if($count == 0) {
                return response()->json(['message' => 'post graduate not found', 'post graduate' => $postGradautes], 404);
            }
            for($i = 0; $i < $count; $i++) {
                $postGradautes[$i]->created_by = json_decode($postGradautes[$i]->created_by);
            }

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
}
