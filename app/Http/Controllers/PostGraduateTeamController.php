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
                $team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 0)->paginate(10);
                if (!$postGradautes) {
                    return response()->json(['message' => 'something went wrong', 'error' => 'contact is not found'], 404);
                }

                $count = count($postGradautes);
                
                if($count == 0) {
                    return response()->json(['message' => 'contact is not found'], 404);
                }
                // $post_graduate = [];
                // for($i = 0; $i < $count; $i++) {
                //     if($postGradautes[$i]->is_under_graduate == 0) {
                //         // return response()->json(['count' => $count],200);
                //         $post_graduate[] = Contact::where([['id', $postGradautes[$i]->id],['is_under_graduate', 0]])->get();
                //     }
                    
                // }
                // $count_post_graduate = count($postGradautes);
                // if($count_post_graduate == 0) {
                //     return response()->json(['message' => 'post graduates are not found'], 404);
                // };
                
                return response()->json(['contacts' => $postGradautes], 200);
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
                $team = Team::where('name', $name)->first();
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
}
