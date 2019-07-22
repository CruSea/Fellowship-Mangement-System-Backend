<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\ContactTeam;
use App\Team;
use App\User;
use App\Fellowship;
use JWTAuth;
class GraduateController extends Controller
{
	// store method is not necessary (because graduete can be assigned directrly in 
	// contact controller @addContact method)
    // public function store() {
    // 	try {
    // 		$user = JWTAuth::parseToken()->toUser();
    // 		if($user instanceof User) {
    // 			$request = request()->only('full_name', 'gender', 'phone', 'email', 'acadamic_department', 'team', 'graduation_year');
	   //          $rule = [
	   //              'full_name' => 'required|string|max:255',
	   //              'gender' => 'required|string|max:255',
	   //              'acadamic_department' => 'string|max:255',
	   //              'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
	   //              'email' => 'required|email|max:255|unique:contacts',
	   //              'graduation_year' => 'required|string',
	   //          ];
	   //          $validator = Validator::make($request, $rule);
	   //          if($validator->fails()) {
	   //              return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 500);
	   //          }

	   //          $graduateContact = new Contact();
	   //          $graduateContact->full_name = $request['full_name'];
	   //          $graduateContact->gender = $request['gender'];
	   //          $graduateContact->phone = $request['phone'];
	   //          $graduateContact->email = $request['email'];
	   //          $graduateContact->acadamic_department = $request['acadamic_department'];
	   //          $graduateContact->graduation_year = $request['graduation_year'].'-07-30';
	   //          $graduateContact->fellowship_id = $user->fellowship_id;
	   //          $graduateContact->created_by = json_encode($user);
	   //          $team = Team::where('name', '=', $request['team'])->first();

	   //          if($request['team'] != null && !$team) {
	   //              return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
	   //          }

	   //          if($graduateContact->save()) {
	   //              $contact_team = new ContactTeam();
	   //              $contact_team->team_id = $team->id;
	   //              $contact_team->contact_id = $contact->id;
	   //              $contact_team->save();
	   //              return response()->json(['message' => 'contact added successfully'], 200);
	   //          }
	   //          return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact'], 500);
    // 		} else {
    // 			return response()->json(['error' => 'token expired'], 401);
    // 		}
    // 	} catch(Exception $ex) {
    // 		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
    // 	}
    // }
    public function show($id) {
    	try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$graduate = Contact::find($id);
				if($graduate instanceof Contact) {
					$is_graduate = $graduate->is_this_year_gc;
					if($is_graduate) {
						return response()->json(['graduate' => $graduate], 200);
					} else {
						return response()->json(['error' => 'contact is not this year graduate'], 404);
					}
				} else {
					return respone()->json(['error' => 'contact is not found'], 404);
				}
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
    }
    public function getGraduates() {
    	try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$graduates = Contact::where('is_this_year_gc', '=', 1)->get();
				$count_graduates = count($graduates);
				if($count_graduates == 0) {
					return response()->json(['message' => 'empty graduates'], 404);
				}
				return response()->json(['graduates' => $graduates], 200);
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}

    }
    public function update($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
            }

            $request = request()->only('full_name', 'gender', 'phone', 'acadamic_department', 'graduation_year');
            $contact = Contact::find($id);
            
            if($contact instanceof Contact) {
            	if(!$contact->is_this_year_gc) {
            		return response()->json(['error' => 'contact is not this year graduate'], 404);
            	}
                $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                'email' => 'email|max:255|unique:contacts|nullable',
                'graduation_year' => 'required|string',
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error' , 'error' => $validator->messages()], 500);
                }
                // check weather the phone exists before
                $check_phone_existance = DB::table('contacts')->where('phone', $request['phone'])->exists();
                if($check_phone_existance && $request['phone'] != $contact->phone) {
                    return response()->json(['error' => 'The phone has already been taken'], 400);
                }
                $contact->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $contact->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $contact->phone = isset($request['phone']) ? $request['phone'] : $contact->phone;
                $contact->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                if($contact->update()) {
                    return response()->json(['message' => 'contact updated seccessfully'], 200);
                } 
                return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to update contact'], 500);
            }
            return response()->json(['message' => 'error found', 'error' => 'contact is not found'], 404);

        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function delete($id) {
    	try {
			$user = JWTAuth::parseToken()->toUser();
			if($user instanceof User) {
				$graduate = Contact::find($id);
				if($graduate instanceof Contact) {
					$is_graduate = $graduate->is_this_year_gc;
					if($is_graduate) {
						if($graduate->delete()) {
							return response()->json(['message' => 'graduate is deleted successfully'], 200);
						} else {
							return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'graduate is not deleted'], 500);
						}
					} else {
						return response()->json(['message' => 'contact is not graduate'], 404);
					}
				} else {
					return response()->json(['error' => 'contact is not found'], 404);
				}
			} else {
				return response()->json(['error' => 'token expired'], 401);
			}
		} catch(Exception $ex) {
			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
		}
    }
}

// try {
// 			$user = JWTAuth::parseToken()->toUser();
// 			if($user instanceof User) {

// 			} else {
// 				return response()->json(['error' => 'token expired'], 401);
// 			}
// 		} catch(Exception $ex) {
// 			return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
// 		}
