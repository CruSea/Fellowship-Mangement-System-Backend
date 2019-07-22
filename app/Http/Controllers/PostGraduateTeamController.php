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
use JWTAuth;
class PostGraduateTeamController extends Controller
{
    public function addPostGraduateMember($name) {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
            $postGraduateTeam = new ContactTeam();
            $team = Team::where('name', '=', $name)->first();
            if(!$team) {
                return response()->json(['error' => 'team is not found'], 404);
            }
    		if($user instanceof User) {
    			$request = request()->only('full_name', 'phone', 'gender', 'email', 'acadamic_department', 'graduation_year');
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
                    return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 400);
                }
            
                $postGraduate = new Contact();
                $postGraduate->full_name = $request['full_name'];
                $postGraduate->gender = $request['gender'];
                $postGraduate->phone = $request['phone'];
                $postGraduate->email = $request['email'];
                $postGraduate->acadamic_department = $request['acadamic_department'];
                $postGraduate->graduation_year = $request['graduation_year'].'-07-30';
                $postGraduate->is_under_graduate = false;
                $postGraduate->is_this_year_gc = false;
                $postGraduate->fellowship_id = $user->fellowship_id;
                $postGraduate->created_by = json_encode($user);

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
                        return response()->json(['message' => 'contact added to '. $team->name.' team successfully'], 200);
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
                $team = Team::where('name', '=',$name)->first();
                if(!$team) { 
                    return response()->json(['message' => 'an error occurred', 'error' => 'team is not found'], 500);
                }
                $team_id = $team->id;
                $postGradautes = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                $team_id)->select('contact_id')->get())->get();
                if (!$postGradautes) {
                    return response()->json(['message' => 'something went wrong', 'error' => 'contact is not found'], 404);
                }

                $count = count($postGradautes);
                
                if($count == 0) {
                    return response()->json(['message' => 'contact is not found'], 404);
                }
                $post_graduate = [];
                for($i = 0; $i < $count; $i++) {
                    if($postGradautes[$i]->is_under_graduate == 0) {
                        // return response()->json(['count' => $count],200);
                        $post_graduate = Contact::where([['id', $postGradautes[$i]->id],['is_under_graduate', 0]])->get();
                    }
                    
                }
                $count_post_graduate = count($post_graduate);
                if($count_post_graduate == 0) {
                    return response()->json(['message' => 'post graduates are not found'], 404);
                };
                
                return response()->json(['contacts' => $post_graduate], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
