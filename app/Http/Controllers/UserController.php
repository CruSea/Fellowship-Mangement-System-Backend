<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\User;
use App\Role;
use App\UserRole;
use App\Fellowship;
use Auth;
use Input;
use JWTAuth;
use DateTime;
use Excel;
class UserController extends Controller
{
    public function __construct() {
        $this->middleware('ability:,create-user', ['only' => ['store']]);
        $this->middleware('ability:,edit-user', ['only' => ['updateUser', 'updatePassword']]);
        $this->middleware('ability:,delete-user', ['only' => ['deleteUser']]);
        $this->middleware('ability:,edit-user-status', ['only' => ['updateUserStatus']]);
        $this->middleware('ability:,edit-user-role', ['only' => ['updateUserRole']]);
        $this->middleware('ability:,get-user', ['only' => ['getUserRole', 'getUsers']]);
        $this->middleware('ability:,get-me', ['only' => ['getMe']]);
    }
    protected function store(Request $request) {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
            }
            /*
            * email validation
            */
            $rule = [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:users',
                'role' => 'required|string',
                'email' => 'required|email|string|max:255|unique:users',
                'password' => 'required|string|min:6',

            ];
            $validation = Validator::make($request->all(), $rule);
            if($validation->fails()) {
                return response()->json(['message' => 'email validation error', 'erorr' => $validation->messages()], 500);
            }
            $role = Role::where('name', '=', $request['role'])->first();
            if($role instanceof Role) {
                // $role_id = $role->id;
                $user = new User();
                $user->full_name = $request->input('full_name');
                $user->phone = $request->input('phone');
                $user->email = $request->input('email');
                $user->fellowship_id = $authUser->fellowship_id;
                $user->password = bcrypt($request->input('password'));
                $user->remember_token = str_random(10);
                // $user->updated_at = new DateTime();
                if($user->save()) {
                    // $user_role = Role::find(4);
                    // $role_id = $role->id;
                    // $user_role = Role::find($role_id);
                    $user->roles()->attach($role);
                    // $user->attachRole($user_role);
                    return response()->json(['message' => 'user registered successfully', 'UserRole' => $role], 201);
                }
                else {
                    return response()->json(['error' => 'Ooops! something went wrong'], 500);
                }
            }
            return response()->json(['message' => 'role is not found', 'error' => 'please enter a right role'], 404);
            
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    protected function getMe() {
        try {
            $user = JWtAuth::parseToken()->toUser();
            if(!$user instanceof User) {
                return response()->json(['message' => 'user is not found', 'error' => 'the user you finding is not found'], 404);
            }
            $userRole = DB::table('role_user')->where('user_id', '=', $user->id)->first();
            $role_id = $userRole->role_id;
            $role = Role::find($role_id);

            return response()->json(['user' => $user, 'role' => $role], 200);
        } catch(Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    protected function getUsers() {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
            }
            $users = User::all();
            return response()->json(['users' => $users], 200);
        } catch(Exception $ex) {
            return response()->json(['error' => $ex->getMessage()] ,$ex->getStatusCode());
        }
    }
    protected function updateUser() {
        try {
            $getUser = JWTAuth::parseToken()->toUser();
            $request = request()->only('full_name', 'phone', 'email');
            $rules = [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                'email' => 'required|email|max:255'

            ];
            $validator = Validator::make($request, $rules);
            if($validator->fails()) {
                return response()->json(['error' => 'validation error', 'message' => $validator->messages()], 500);
            }
            // check weather the email exists before
            $check_email_existance = User::where('email', '=',$request['email'])->exists();
            if($check_email_existance && $request['email'] != $getUser->email) {
                return response()->json(['error' => 'Ooops! this email is occupied'], 400);
            }
            // check weather the phone exists before
            $check_phone_existance = User::where('phone', $request['phone'])->exists();
            if($check_phone_existance && $request['phone'] != $getUser->phone) {
                return response()->json(['error' => 'The phone has already been taken.'], 400);
            }
            $oldUser = User::find($getUser->id);
            //$oldUser = User::find($id);
            
            if($oldUser instanceof User) {
                $oldUser->full_name = isset($request['full_name']) ? $request['full_name'] : $oldUser->full_name;
                $oldUser->phone = isset($request['phone']) ? $request['phone'] : $oldUser->phone;
                $oldUser->email = isset($request['email']) ? $request['email'] : $oldUser->email;
                if($oldUser->update()) {
                    return response()->json(['message' => 'user updated successfully'], 200);
                }
                else {
                    return response()->json(['error' => 'Ooops! something went wrong'], 500);
                }
            } else {
                return response()->json(['message' => 'user is not found'], 404);
            }
            return response()->json(['message' => 'user is: ', 'user' => $oldUser->id], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'somthing went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    protected function updatePassword() {
        //$user = User::find($id);
        try {
            $user = JWTAuth::parseToken()->toUser();
            $request = request()->only('old_password');
            $requestNewPassword = request()->only('new_password');
            //old password validation
            $password_rule = [
                'old_password' => 'required|string|min:6',
            ];
            $validate_password = Validator::make($request, $password_rule);
            if($validate_password->fails()) {
                return response()->json(['message' => 'password validation error', 'error' => $validate_password->messages()], 500);
            }

            $old_password = $request['old_password'];
            $encrypt_oldPassword = bcrypt($old_password);
            if(Hash::check($old_password, $user->password)) {
                //new password validation
                $new_password_rule = [
                    'new_password' => 'required|string|min:6',
                ];
                $validate_new_password = Validator::make($requestNewPassword, $new_password_rule);
                if($validate_new_password->fails()) {
                    return response()->json(['message' => 'password validation error', 'error' => $validate_new_password->messages()], 500);
                }
                $user->password = bcrypt($requestNewPassword['new_password']);
                $user->updated_at = new DateTime();
                if($user->save()) {
                    return response()->json(['message' => 'password updated successfully'], 200);
                }
                else {
                    return response()->json(['message' => 'Ooops! somthing went wrong while updating password'], 500);
                }
            }
            else {
                return response()->json(['message' => 'password mismatch error', 'error' => "the password you entered doesn't not match"], 200);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    protected function deleteUser($id) {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
            }
            $user = User::find($id);
            if(!$user) {
                return response()->json(['error' => 'user is not found'], 404);
            }        
            $getUser = JWTAuth::parseToken()->toUser();
            // check own delete
            if($user == $getUser) {
                return response()->json(['message' => 'if you want to delete yourself please delete your account'], 403);
            }
            if($user->delete()) {
                return response()->json(['message' => 'user deleted successfully'], 200);
            }
            else {
                return response()->json(['error' => 'Ooops! something went wrong'], 500);
            }
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }

    }
    protected function deleteAccount() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                // if($user->delete()) {
                //     return response()->json(['message' => 'your account will be deleted with in two days', 'response' => 'your account will be active if you use your account with in two days'], 200);
                // }
            }
            return response()->json(['message' => 'authentication error', 'error' => 'user is not authorized to do this action'], 401);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    protected function updateUserStatus($id) {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
            }
            $user = User::find($id);
            $request = request()->only('status');
            
            if(!$user) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            $status_rule = [
                'status' => 'boolean',
            ];
            $validate_status = Validator::make($request, $status_rule);
            if($validate_status->fails()) {
                return response()->json(['message' => 'status validation error', 'error' => $validate_status->messages()], 500);
            }
            $user->status = $request['status'];
            $user->updated_at = new DateTime();
            if($user->save()) {
                return response()->json(['message' => 'status updated successfully'], 200);
            } else {
                return response()->json(['error' => 'Ooops! something went wrong'], 500);
            }
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => $ex->getMessage], $ex->getStatusCode());
        }
    }
    protected function updateUserRole(Request $request, $id) {
        $authUser = JWTAuth::parseToken()->toUser();
        if(!$authUser) {
            return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
        }
        $user = User::find($id);
        if(!$user) {
            return response()->json(['error' => 'user is not found'], 404);
        }
        $rule = [
            'role' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $rule);
        if($validator->fails()) {
            return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
        }
        $getRole = Role::where('name', '=', $request->input('role'))->first();
        // $getRole = Role::find($request->input('role'));
        if(!$getRole) {
            return response()->json(['error' => 'specified role is not found'], 404);
        }
        $role_id = $getRole->id;
        if($role_id == 1) {
            return response()->json(['error' => 'this role is not found'], 404);
        }
        $user->roles()->detach($user->roles->first());
        $user->roles()->attach($role_id);
        if($user->update()) {
            return response()->json(['message' => 'role updated successfully'], 200);
        } else {
            return response()->json(['error' => '!Ooops something went wrong'], 500);
        }
    }
    protected function getUserRole($id) {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['message' => 'authentication error', 'error' => "not authorized to this action"], 404);
            }
            $user = User::find($id);
            if(!$user) {
                return response()->json(['message' => 'an error occurred', 'error' => 'specified user is not found'], 404);
            }
            $getUserRole = DB::table('role_user')->where('user_id', '=', $id)->first();
            $role_id = $getUserRole->role_id;
            $role = Role::find($role_id);
            return response()->json(['user Role' => $role], 200);
        }catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! somthing went wrong', 'error' => $ex->getMessage], $ex->getStatusCode());
        }
    }
    protected function importExcel()
	{
        
		// if(Input::hasFile('file')){
		// 	$path = Input::file('file')->getRealPath();
		// 	$data = Excel::load($path, function($reader) {
        //     })->get();
        //     $headerRow = $data->first()->keys();
        //     $request = request()->only($headerRow[0], $headerRow[1], $headerRow[2], $headerRow[3], $headerRow[4], $headerRow[5]);
		// 	if(!empty($data) && $data->count()){
		// 		foreach ($data as $key => $value) {
        //             // check weather the phone exists before
        //             // $check_phone_existance = DB::table('users')->where('phone', $value->phone)->exists();
        //             // if($check_phone_existance) {
        //             //     return response()->json(['error' => 'Ooops! This phone number is already in the database', 'header row' => $headerRow], 500);
        //             // }
        //             // // check weather the email exists before
        //             // $check_email_existance = DB::table('users')->where('email', $value->email)->exists();
        //             // if($check_email_existance) {
        //             //     return response()->json(['error' => 'Ooops! this email is occupied'], 500);
        //             // }
                
        //             /*
        //             * phone validation
        //             */
        //             $phone_rule = [
        //                 $headerRow[2] => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
        //             ];
        //             // $phone_validation = CsvValidator::make($path, $phone_rule);
        //             // if($phone_validation->fails()){
        //             //     return response()->json(['message' => 'phone validation error', 'error' => 'the phone number is not valid'], 500);
        //             // }
        //             // $phone_validation = Validator::make($request, $phone_rule);
        //             // if($phone_validation->fails()) {
        //             //     return response()->json(['message' => 'phone validation error', 'error' => 'the phone number is not valid'], 500);
        //             // }
        //             /*
        //             * email validation
        //             */
        //             /************************************------------------------------------------ */
        //             // $email_rule = [
        //             //     $headerRow[4] => 'required|email|string|max:255',
        //             // ];
        //             // $email_validation = Validator::make($request, $email_rule);
        //             // if($email_validation->fails()) {
        //             //     return response()->json(['message' => 'email validation error', 'erorr' => 'The email is not valid'], 500);
        //             // }
        //             // // first name, last name, and university validation
        //             // $rules = [
        //             //     $headerRow[0] => 'required|string|max:255',
        //             //     $headerRow[1] => 'required|string|max:255',
        //             //     $headerRow[2] => 'required|string|max:255',
        //             // ];
        //             // $validation = Validator::make($request, $rules);
        //             // if($validation->fails()) {
        //             //     return response()->json(['message' => 'validation error','error' => 'validation error'], 500);
        //             // }
        //             /************************************------------------------------------------ */
        //             $insert[] = ['firstname' => $value->firstname, 'lastname' => $value->lastname, 
        //             'phone' => $value->phone, 'university' => $value->university, 'email' => $value->email, 'password' => bcrypt($value->password)];
        //         }
		// 		if(!empty($insert)){
		// 			DB::table('users')->insert($insert);
        //             dd('Insert Record successfully.');
        //             return response()->json(['message' => 'user added successfully'], 200);
		// 		}
        //     }
        //     else {
        //         return response()->json(['message' => 'file is empty', 'error' => 'No user is found in the file'], 404);
        //     }
        // }
        // return response()->json(['message' => 'File not found', 'error' => 'User File is not provided'], 404);
		// //return back();
	}
}
