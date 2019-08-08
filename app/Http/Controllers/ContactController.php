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
use DateTime;
use Carbon\Carbon;

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
                'team' => 'string|min:1|nullable',
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
            if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
            }
            $check_phone_existance = Contact::where('phone', $phone_number)->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'The phone has already been taken'], 400);
            }

            // check whethe contact is under graduate
            // if user is post graduate return error
            $this_year_gc = false;
            $graduationYear = $request['graduation_year'].'-07-30';
            $parse_graduation_year = Carbon::parse($graduationYear);
            $today = Carbon::parse(date('Y-m-d'));
            $difference = $today->diffInDays($parse_graduation_year, false);
            
            if($difference <= 0) {
                return response()->json(['error' => 'graduation year is not valid for under graduate member'], 400);
            } else if($difference < 380 && $difference > 0) {
                $this_year_gc = true;
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
            $contact->is_this_year_gc = $this_year_gc;
            $contact->created_by = $user->full_name;
            $team = Team::where([['name', '=', $request['team']], ['fellowship_id', '=', $user->fellowship_id]])->first();

            if($request['team'] != null && !$team) {
                return response()->json(['message' => 'team is not found', 'error' => 'please add '. $request['team']. ' team first before adding contact to '. $request['team']. ' team'], 404);
            }

            if($contact->save()) {
                // if($contact->team_id != null) {
                $contact_team = new ContactTeam();
                $contact_team->team_id = $team->id;
                $contact_team->contact_id = $contact->id;
                $contact_team->save();
                // }
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
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $contact = Contact::find($id);
            if($contact instanceof Contact && $contact->fellowship_id == $user->fellowship_id) {
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
            $contacts = Contact::where([['is_under_graduate', '=', 1],['fellowship_id', '=', $user->fellowship_id]])->paginate(10);
            $countContact = Contact::count();
            $count_under_graduate = count($contacts);
            if($countContact == 0) {
                return response()->json(['message' => 'contact is not available'], 404);
            }
            if($count_under_graduate == 0) {
                return response()->json(['message' => 'under graduate member is not found'], 404);
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
            
            if($contact instanceof Contact && $contact->fellowship_id == $user->fellowship_id) {
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
                if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                    return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
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
                // check whethe contact is under graduate
                $this_year_gc = false;
                $graduationYear = $request['graduation_year'].'-07-30';
                $parse_graduation_year = Carbon::parse($graduationYear);
                $today = Carbon::parse(date('Y-m-d'));
                $difference = $today->diffInDays($parse_graduation_year, false);
                
                if($difference <= 0) {
                    return response()->json(['error' => 'graduation year is not valid for under graduate member'], 400);
                } else if($difference < 380 && $difference > 0) {
                    $this_year_gc = true;
                }
                $contact->full_name = isset($request['full_name']) ? $request['full_name'] : $contact->full_name;
                $contact->gender = isset($request['gender']) ? $request['gender'] : $contact->gender;
                $contact->phone = isset($request['phone']) ? $phone_number : $contact->phone;
                $contact->email = isset($request['email']) ? $request['email'] : $contact->email;
                $contact->acadamic_department = isset($request['acadamic_department']) ? $request['acadamic_department'] : $contact->acadamic_department;
                $contact->graduation_year = isset($request['graduation_year']) ? $request['graduation_year'].'-07-30' : $contact->graduation_year;
                $contact->is_this_year_gc = $this_year_gc;
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
    public function deleteContact($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $contact = Contact::find($id);
            if($contact instanceof Contact && $contact->fellowship_id == $user->fellowship_id) {
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
    public function searchContact(Request $request) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $input = $request->all();
                $contacts = Contact::query();
                $search = Input::get('search');
                if($search) {
                    $contacts = $contacts->where([['full_name', 'LIKE', '%'.$search.'%'], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', true]])->orWhere([['phone', 'LIKE','%'.$search.'%'], ['fellowship_id', '=', $user->fellowship_id], ['is_under_graduate', '=', true]])->get();
                    if(count($contacts) > 0) {
                        return $contacts;
                    }
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    // public function searchContact(Request $request) {
    //     try {
    //         $user = JWTAuth::parseToken()->toUser();
    //         if($user instanceof User) {
    //             // if($request->ajax()) {
    //                 $output = "";
    //                 $contacts = Contact::where('full_name', 'LIKE', '%'.$request->input('search').'%')->get();
    //                 if($contacts) {
    //                     foreach ($contacts as $key => $contact) {
    //                         $output.='<tr>'.
    //                         '<td>'.$contact->id.'</td>'.
    //                         '<td>'.$contact->full_name.'</td>'.
    //                         '<td>'.$contact->phone.'</td>'.
    //                         '</tr>';
    //                     }
    //                     return response()->json(['contacts' => $output], 200);
    //                 }
    //             // }
    //         } else {
    //             return response()->json(['error' => 'token expired'], 200);
    //         }
    //     } catch(Exception $ex) {
    //         return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
    //     }
    // }
    public function importContact() { 
        $user = JWTAuth::parseToken()->toUser();
        if(!$user) {
            return response()->json(['error' => 'token expired'], 401);
        }
        $count_add_contacts = 0;
		if(Input::hasFile('file')){
            $path = Input::file('file')->getRealPath();
			$data = Excel::load($path, function($reader) {
            })->get();
            $headerRow = $data->first()->keys();
            $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3], $headerRow[4], $headerRow[5], $headerRow[6]);
			if(!empty($data) && $data->count()){
				foreach ($data as $key => $value) {
                    // phone validation 
                    if($value->phone == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => "validation error", 'error' => "phone can't be null"], 403);
                        }
                        return response()->json(['message' => "validation error", 'error' => "phone can't be null"], 403);
                    }
                    if($value->full_name == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "full name can't be null"], 403);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "full name can't be null"], 403);
                    }
                    if($value->gender == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts. ' contacts added yet','message' => 'validation error', 'error' => "gender can't be null"], 403);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "gender can't be null"], 403);
                    }
                    if($value->acadamic_department == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => 'validation error', 'error' => "acadamic department year can't be null"], 404);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "acadamic department year can't be null"], 404);
                    }
                    if($value->graduation_year == null) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','message' => 'validation error', 'error' => "graduation year can't be null"], 404);
                        }
                        return response()->json(['message' => 'validation error', 'error' => "graduation year can't be null"], 404);
                    }
                    // check whethe contact is under graduate
                    $this_year_gc = false;
                    $graduationYear = $value->graduation_year.'-07-30';
                    $parse_graduation_year = Carbon::parse($graduationYear);
                    $today = Carbon::parse(date('Y-m-d'));
                    $difference = $today->diffInDays($parse_graduation_year, false);
                    
                    if($difference <= 0) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => 'graduation year is not valid for under graduate member'], 400);
                        }
                        return response()->json(['error' => 'graduation year is not valid for under graduate member'], 400);
                    } else if($difference < 380 && $difference > 0) {
                        $this_year_gc = true;
                    }
                    $team = Team::where([['name', '=', $value->team], ['fellowship_id', '=', $user->fellowship_id]])->first();
                    if($value->team != null && !$team) {
                        if($count_add_contacts > 0) {
                            return response()->json(['response' => $count_add_contacts.' contacts added yet','error' => $value->team.' team is not found, please add '.$value->team.' team first if you want to add contact to '.$value->team.' team'], 400);
                        }
                        return response()->json(['error' => $value->team.' team is not found, please add '.$value->team.' team first if you want to add contact to '.$value->team.' team'], 400);
                    }
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
                    if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                    }
                    // check weather the phone exists before
                    $check_phone_existance = Contact::where('phone', $phone_number)->exists();
                    // check weather the email exists before
                    $check_email_existance = Contact::where([['email', '=',$value->email],['email', '!=', null]])->exists();
                    if(!$check_phone_existance && !$check_email_existance && strlen($phone_number) == 13) {
                        $contact = new Contact();
                        $contact->full_name = $value->full_name;
                        $contact->gender = $value->gender;
                        $contact->phone = $phone_number;
                        $contact->email = $value->email;
                        $contact->acadamic_department = $value->acadamic_department;
                        $contact->graduation_year = $value->graduation_year.'-07-30';
                        $contact->fellowship_id = $user->fellowship_id;
                        $contact->is_under_graduate = true;
                        $contact->is_this_year_gc = $this_year_gc;
                        $contact->created_by = $user->full_name;
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
                }
                if($count_add_contacts == 0) {
                    return response()->json(['message' => 'no contact is added'], 404);
                }
                return response()->json(['message' => $count_add_contacts.' contacts added successfully'], 200);
            }
            else {
                return response()->json(['message' => 'file is empty', 'error' => 'No contact is found in the file'], 404);
            }
        }
        return response()->json(['message' => 'File not found', 'error' => 'Contact File is not provided'], 404);
    }
    public function exportContact() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                // $contacts = Contact::select('full_name', 'phone', 'gender','graduation_year')->get()->toArray();
                // Excel::create('contacts', function($excel) use ($contacts){
                //     $excel->sheet('sheet 1', function($sheet) use ($contacts){
                //         $sheet->fromArray($contacts);
                //     });
                // })->download('xlsx');
                $contacts = Contact::where([['is_under_graduate', '=', 1], ['fellowship_id', '=', $user->fellowship_id]])->get()->toArray();
                if(count($contacts) == 0) {
                    return response()->json(['message' => 'under graduate member is not found'], 404);
                }
                $contact_array[] = array('full_name','gender', 'phone', 'email', 'acadamic_department', 'graduation_year', 'created_by', 'created_at', 'updated_at');
                foreach ($contacts as $contact) {
                    $contact_array[] = array(
                        'full_name' => $contact->full_name,
                        'gender' => $contact->gender,
                        'phone' => $contact->phone,
                        'email' => $contact->email,
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
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
