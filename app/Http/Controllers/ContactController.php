<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\Team;
use App\User;
use App\Fellowship;
use Input;
use JWTAuth;
use Excel;
use CsvValidator;

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
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to add contacts"], 404);
            }
            $request = request()->only('full_name', 'gender', 'phone', 'acadamic_department');
            $phone_rule = [
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
            ];
            $phone_validator = Validator::make($request, $phone_rule);
            if($phone_validator->fails()) {
                return response()->json(['message' => 'phone validation error' , 'error' => 'The phone is not valid'], 500);
            }
            // check weather the phone exists before
            $check_phone_existance = DB::table('contacts')->where('phone', $request['phone'])->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'Ooops! This phone number is already in the database'], 500);
            }
            $rules = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
            ];
            $validator = Validator::make($request, $rules);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            $contact = new Contact();
            $contact->full_name = $request['full_name'];
            $contact->gender = $request['gender'];
            $contact->phone = $request['phone'];
            $contact->acadamic_department = $request['acadamic_department'];
            $contact->fellowship_id = $user->fellowship_id;

            if($contact->save()) {
                return response()->json(['message' => 'contact added successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to save the contact'], 500);
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getContacts() {
        try {
            $contacts = new Contact();
            $countContact = DB::table('contacts')->count();
            if($countContact == 0) {
                return response()->json(['contact is not available'], 404);
            }
            return response()->json(['contacts' => $contacts->paginate(10)], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex], 500);
        }
    }
    public function getContact($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($contact = Contact::find($id)) {
                if(!$user) {
                    return response()->json(['message' => 'authentication error', 'error' => "not authorized to add contacts"], 404);
                }
                return response()->json(['contact' => $contact], 200);
            }
            return response()->json(['message' => 'an error found', 'error' => 'contact is not found'], 404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function updateContact($id) {
        try {
            $request = request()->only('full_name', 'gender', 'phone', 'acadamic_department');
            $contact = Contact::find($id);
            $phone_rule = [
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
            ];
            $phone_validator = Validator::make($request, $phone_rule);
            if($phone_validator->fails()) {
                return response()->json(['message' => 'phone validation error' , 'error' => 'The phone is not valid'], 500);
            }
            // check weather the phone exists before
            $check_phone_existance = DB::table('contacts')->where('phone', $request['phone'])->exists();
            if($check_phone_existance && $request['phone'] != $contact->phone) {
                return response()->json(['error' => 'Ooops! this phone number is already in the database'], 500);
            }
            $rules = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
            ];
            $validator = Validator::make($request, $rules);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            if($contact instanceof Contact) {
                $contact->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $contact->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $contact->phone = isset($request['phone']) ? $request['phone'] : $contact->phone;
                $contact->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                if($contact->update()) {
                    return response()->json(['message' => 'contact updated seccessfully'], 200);
                } 
                return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'unable to update contact'], 500);
            }

        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function importContact()
	{
        
		if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
			$data = Excel::load($path, function($reader) {
            })->get();
            $headerRow = $data->first()->keys();
            $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3]);
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
                    $insert[] = ['firstname' => $value->firstname, 'lastname' => $value->lastname, 
                    'phone' => $value->phone, 'university' => $value->university];
                }
				if(!empty($insert)){
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
