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
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('full_name', 'gender', 'phone', 'email', 'acadamic_department', 'team', 'graduation_year');
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
            $check_phone_existance = Contact::where('phone', $phone_number)->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'The phone has already been taken'], 400);
            }
            $contact = new Contact();
            $contact->full_name = $request['full_name'];
            $contact->gender = $request['gender'];
            $contact->phone = $phone_number;
            $contact->email = $request['email'];
            $contact->acadamic_department = $request['acadamic_department'];
            $contact->graduation_year = $request['graduation_year'].'-07-30';
            $contact->fellowship_id = $user->fellowship_id;
            $contact->is_under_graduate = true;
            $contact->is_this_year_gc = false;
            $contact->created_by = json_encode($user);
            $team = Team::where('name', '=', $request['team'])->first();

            if($request['team'] != null && !$team) {
                return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
            }

            if($contact->save()) {
                if($contact->team_id != null) {
                    $contact_team = new ContactTeam();
                    $contact_team->team_id = $team->id;
                    $contact_team->contact_id = $contact->id;
                    $contact_team->save();
                }
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
                    return response()->json(['error' => 'token expired'], 401);
                }
                $contact->created_by = json_decode($contact->created_by);
                return response()->json(['contact' => $contact], 200);
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
                return response()->json(['error' => 'token expired'], 401);
            }

            // $contacts = Contact::all();
            $contacts = Contact::where('is_under_graduate', '=', 1)->paginate(10);
            $countContact = Contact::count();
            if($countContact == 0) {
                return response()->json(['message' => 'contact is not available'], 404);
            }
            for($i = 0; $i < $countContact; $i++) {
                $contacts[$i]->created_by = json_decode($contacts[$i]->created_by);
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
                return response()->json(['error' => 'token expired'], 401);
            }

            $request = request()->only('full_name', 'gender', 'phone', 'email','acadamic_department', 'graduation_year');
            $contact = Contact::find($id);
            
            if($contact instanceof Contact) {
                $rule = [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'acadamic_department' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:contacts',
                'email' => 'email|max:255|nullable',
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
    public function importContact() { 
        $user = JWTAuth::parseToken()->toUser();
        if(!$user) {
            return response()->json(['error' => 'token expired'], 401);
        }
        // $contact = Contact::all();
        // dd($contact->phone);
        $count_add_contacts = 0;
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
                        dd('validation error phone can not be null');
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
                    $team = Team::where('name', '=', $value->team)->first();
                    // check weather the phone exists before
                    $phone_number  = $value->phone;
                    $contact0 = Str::startsWith($value->phone, '0');
                    $contact9 = Str::startsWith($value->phone, '9');
                    $contact251 = Str::startsWith($value->phone, '251');
                    if($contact0) {
                        $phone_number = Str::replaceArray("0", ["+251"], $value->phone);
                    }
                    else if($contact9) {
                        $phone_number = Str::replaceArray("9", ["+2519"], $value->phone);
                    }
                    else if($contact251) {
                        $phone_number = Str::replaceArray("251", ['+251'], $value->phone);
                    }
                    $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                    if(!$check_phone_existance) {
                        $contact = new Contact();
                        $contact->full_name = $value->full_name;
                        $contact->gender = $value->gender;
                        $contact->phone = $phone_number;
                        $contact->email = $value->email;
                        $contact->acadamic_department = $value->acadamic_department;
                        $contact->graduation_year = $value->graduation_year.'-07-30';
                        $contact->fellowship_id = $user->fellowship_id;
                        $contact->is_under_graduate = true;
                        $contact->is_this_year_gc = false;
                        $contact->created_by = json_encode($user);
                        if($contact->save()) {
                            if($value->team != null && $team instanceof Team) {
                                $contact_team = new ContactTeam();
                                $contact_team->team_id = $team->id;
                                $contact_team->contact_id = $contact->id;
                                $contact_team->save();
                            }
                            $count_add_contacts++;
                        }
                    }
                    
                    // $insert[] = ['full_name' => $value->full_name, 'gender' => $value->gender, 
                    // 'phone' => $value->phone, 'email' => $value->email, 'acadamic_department' => $value->acadamic_department, 'graduation_year' => $value->graduation_year.'-07-30', 'fellowship_id' => $user->fellowship_id, 'is_under_graduate' => true,
                    //     'is_this_year_gc' => false, 'created_by' => json_encode($user),'created_at' => new DateTime(), 'updated_at' => new DateTime()];
                    // $lastContact = Contact::latest()->first();
                    // $insertTeam[] = ['team_id' => $team->id, 'contact_id' => $lastContact->id];
                }
                dd($count_add_contacts.' contacts add successfully');
				// if(!empty($insert)){
                    // Contact::insert($insert);
					// DB::table('contacts')->insert($insert);
     //                dd('Insert Record successfully.');
				// }
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
                return response()->json(['error' => 'token expired'], 401);
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
    public function exportContact() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $contacts = Contact::select('full_name', 'phone', 'gender','graduation_year')->get()->toArray();
                Excel::create('contacts', function($excel) use ($contacts){
                    $excel->sheet('sheet 1', function($sheet) use ($contacts){
                        $sheet->fromArray($contacts);
                    });
                })->download('xlsx');
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
