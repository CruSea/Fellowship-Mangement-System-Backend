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
use Input;
use JWTAuth;
use Excel;
use CsvValidator;
use DateTime;

class ContactController extends Controller
{
    public function __construct() {
        $this->middleware('ability:,create-contact', ['only' => ['addContact', 'importContact', 'addTeam']]);
        $this->middleware('ability:,edit-contact', ['only' => ['updateContact']]);
        $this->middleware('ability:,delete-contact', ['only' => ['deleteContact']]);
        $this->middleware('ability:,get-contact', ['only' => ['getContacts', 'getContacts']]);
    }
    public function addContact() {
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
            $contact = new Contact();
            $contact->full_name = $request['full_name'];
            $contact->gender = $request['gender'];
            $contact->phone = $request['phone'];
            $contact->email = $request['email'];
            $contact->acadamic_department = $request['acadamic_department'];
            $contact->graduation_year = $request['graduation_year'].'-07-30';
            $contact->fellowship_id = $user->fellowship_id;
            $contact->created_by = json_encode($user);
            $team = Team::where('name', '=', $request['team'])->first();

            if($request['team'] != null && !$team) {
                return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
            }

            if($contact->save()) {
                $contact_team = new ContactTeam();
                $contact_team->team_id = $team->id;
                $contact_team->contact_id = $contact->id;
                $contact_team->save();
                return response()->json(['message' => 'contact added successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact'], 500);
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getContact($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($contact = Contact::find($id)) {
                if(!$user) {
                    return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
                }
                return response()->json(['contact' => $contact->full_name], 200);
            }
            return response()->json(['message' => 'an error found', 'error' => 'contact is not found'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getContacts() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
            }
            // $contacts = Contact::all();
            $contacts = Contact::where('is_under_graduate', '=', 1)->get();
            $countContact = Contact::count();
            if($countContact == 0) {
                return response()->json(['message' => 'contact is not available'], 404);
            }
            return response()->json(['contacts' => $contacts], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex], 500);
        }
    }
    public function updateContact($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
            }

            $request = request()->only('full_name', 'gender', 'phone', 'acadamic_department');
            $contact = Contact::find($id);
            
            if($contact instanceof Contact) {
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
                    return response()->json(['message' => 'phone validation error' , 'error' => $validator->messages()], 500);
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
    public function importContact() { 
        $user = JWTAuth::parseToken()->toUser();
        if(!$user) {
            return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
        }
        // $contact = Contact::all();
        // dd($contact->phone);
		if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
			$data = Excel::load($path, function($reader) {
            })->get();
            $headerRow = $data->first()->keys();
            $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3], $headerRow[4], $headerRow[5], $headerRow[6]);
			if(!empty($data) && $data->count()){
				foreach ($data as $key => $value) {
                    // check weather the phone exists before
                    // $check_phone_existance = DB::table('users')->where('phone', $value->phone)->exists();
                    // if($check_phone_existance) {
                    //     return response()->json(['error' => 'Ooops! This phone number is already in the database', 'header row' => $headerRow], 500);
                    // }
                    // // check weather the email exists before
                    // $check_email_existance = DB::table('users')->where('email', $value->email)->exists();
                    // if($check_email_existance) {
                    //     return response()->json(['error' => 'Ooops! this email is occupied'], 500);
                    // }
                
                    /*
                    * phone validation
                    */
                    // $phone_rule = [
                    //     $headerRow[2] => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
                    // ];
                    // $phone_validation = CsvValidator::make($path, $phone_rule);
                    // if($phone_validation->fails()){
                    //     return response()->json(['message' => 'phone validation error', 'error' => 'the phone number is not valid'], 500);
                    // }
                    // $phone_validation = Validator::make($request, $phone_rule);
                    // if($phone_validation->fails()) {
                    //     return response()->json(['message' => 'phone validation error', 'error' => 'the phone number is not valid'], 500);
                    // }
                    /*
                    * email validation
                    */
                    /************************************------------------------------------------ */
                    // $email_rule = [
                    //     $headerRow[4] => 'required|email|string|max:255',
                    // ];
                    // $email_validation = Validator::make($request, $email_rule);
                    // if($email_validation->fails()) {
                    //     return response()->json(['message' => 'email validation error', 'erorr' => 'The email is not valid'], 500);
                    // }
                    // // first name, last name, and university validation
                    // $rules = [
                    //     $headerRow[0] => 'required|string|max:255',
                    //     $headerRow[1] => 'required|string|max:255',
                    //     $headerRow[2] => 'required|string|max:255',
                    // ];
                    // $validation = Validator::make($request, $rules);
                    // if($validation->fails()) {
                    //     return response()->json(['message' => 'validation error','error' => 'validation error'], 500);
                    // }
                    /************************************------------------------------------------ */

                    /* csv validation =================================================================*/
                    // $rules = [
                    //     'firstname' => 'required|string|max:255',
                    //     'lastname' => 'required|string|max:255',
                    //     'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
                    //     'university' => 'required|string|max:255',
                    // ];
                    // //return response()->json(['data' => $data, 'path' => $path2], 200);
                    // $csvValidator = CsvValidator::make($input, $rules);
                    // if($csvValidator->fails()) {
                    //     return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
                    // }
                    /* csv validation =================================================================*/

                    // phone validation 
                    if($value->phone == null) {
                        return response()->json(['message' => "validation error", 'error' => "phone can't be null"], 403);
                    }
                    // if($value->phone == $contact->phone) {
                    //     dd('phone duplication error.');
                    //     return response()->json(['message' => 'duplication error', 'error' => 'Phone has already been taken.'], 400);
                    // }
                    // full_name validation
                    if($value->full_name == null) {
                        return response()->json(['message' => 'validation error', 'error' => "full name can't be null"], 403);
                    }
                    if($value->gender == null) {
                        return response()->json(['message' => 'validation error', 'error' => "gender can't be null"], 403);
                    }
                    $insert[] = ['full_name' => $value->full_name, 'gender' => $value->gender, 
                    'phone' => $value->phone, 'email' => $value->email, 'team' => $value->team, 'acadamic_department' => $value->acadamic_department, 'graduation_year' => $value->graduation_year, 'fellowship_id' => $user->fellowship_id, 'created_by' => $user->full_name,'created_at' => new DateTime(), 'updated_at' => new DateTime()];
                }
				if(!empty($insert)){
                    // Contact::insert($insert);
					DB::table('contacts')->insert($insert);
                    dd('Insert Record successfully.');

                    return response()->json(['message' => 'contacts added successfully'], 200);
				}
            }
            else {
                return response()->json(['message' => 'file is empty', 'error' => 'No contact is found in the file'], 404);
            }
        }
        return response()->json(['message' => 'File not found', 'error' => 'Contact File is not provided'], 404);
    }
    public function deleteContact($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to do this action"], 401);
            }

            if($contact = Contact::find($id)) {
                if($contact->delete()) {
                    return response()->json(['message' => 'contact deleted successfully'], 200);
                }
                return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to delete contact'], 500);
            } 
            return response()->json(['message' => 'an error occurred', 'error' => 'contact is not found'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
