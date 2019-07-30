<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Contact;
use App\ContactTeam;
use App\Team;
use App\User;
use App\Fellowship;
use JWTAuth;
class GraduateController extends Controller
{
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
                return response()->json(['error' => 'token expired'], 401);
            }

            $request = request()->only('full_name', 'gender', 'phone', 'email', 'acadamic_department', 'graduation_year');
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
                $phone_number  = $request['phone'];
                $contact0 = Str::startsWith($request['phone'], '0');
                $contact9 = Str::startsWith($request['phone'], '9');
                $contact251 = Str::startsWith($request['phone'], '251');
                if($contact0) {
                    $phone_number = Str::replaceArray("0", ["+251"], $request['phone']);
                }
                else if($contact9) {
                    $phone_number = Str::replaceArray("9", ["+2519"], $request['phone']);
                }
                else if($contact251) {
                    $phone_number = Str::replaceArray("251", ['+251'], $request['phone']);
                }
                // check weather the phone exists before
                $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                if($check_phone_existance && $phone_number != $contact->phone) {
                    return response()->json(['error' => 'The phone has already been taken'], 400);
                }
                // check email existance before
                if($request['email'] != null) {
                    $check_email_existance = Contact::where('email', '=',$request['email'])->exists();
                    if($check_email_existance && $request['email'] != $contact->email) {
                        return response()->json(['error' => 'The email has already been taken'], 400);
                    }
                }
                $contact->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $contact->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $contact->phone = isset($request['phone']) ? $phone_number : $contact->phone;
                $contact->email = isset($request['email']) ? $request['email'] : $contact->email;
                $contact->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                $contact->graduation_year = isset($request['graduation_year']) ? $request['graduation_year'].'-07-30' : $contact->graduation_year;
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