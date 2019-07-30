<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\User;
use App\Role;
use App\UserRole;
use App\Fellowship;
use App\Notification;
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
                return response()->json(['error' => 'token expired'], 401);
            }
            $rule = [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:users',
                'role' => 'required|string',
                'email' => 'required|email|string|max:255|unique:users',
                'password' => 'required|string|min:6',

            ];
            $validation = Validator::make($request->all(), $rule);
            if($validation->fails()) {
                return response()->json(['message' => 'validation error', 'erorr' => $validation->messages()], 500);
            }
            $phone_number  = $request->input('phone');
            $contact0 = Str::startsWith($request->input('phone'), '0');
            $contact9 = Str::startsWith($request->input('phone'), '9');
            $contact251 = Str::startsWith($request->input('phone'), '251');
            if($contact0) {
                $phone_number = Str::replaceArray("0", ["+251"], $request->input('phone'));
            }
            else if($contact9) {
                $phone_number = Str::replaceArray("9", ["+2519"], $request->input('phone'));
            }
            else if($contact251) {
                $phone_number = Str::replaceArray("251", ['+251'], $request->input('phone'));
            }
            
            $role = Role::where('name', '=', $request->input('role'))->first();
            if($request->input('role') == 'super-admin') {
                return response()->json(['message' => 'role is not found', 'error' => 'please enter a right role'], 404);
            }
            $fellowship_id = $authUser->fellowship_id;
            $fellowship = Fellowship::find($fellowship_id);
            $notification = new Notification();
            if($role instanceof Role) {
                // $role_id = $role->id;
                $user = new User();
                $user->full_name = $request->input('full_name');
                $user->phone = $phone_number;
                $user->email = $request->input('email');
                $user->fellowship_id = $authUser->fellowship_id;
                $user->password = bcrypt($request->input('password'));
                $user->remember_token = str_random(10);
                // $user->updated_at = new DateTime();
                if($user->save()) {
                    $user->roles()->attach($role);
                    $notification->notification = $authUser->full_name.' added '.$user->full_name.' as '. $role->name.' for '. $fellowship->university_name;
                    $notification->save();
                    return response()->json(['message' => 'user registered successfully'], 201);
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
                return response()->json(['error' => 'token expired'], 401);
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
                return response()->json(['error' => 'token expired'], 401);
            }
            // $users = User::all();
            $users = User::with('roles')->paginate(10);
            return response()->json(['users' => $users], 200);
        } catch(Exception $ex) {
            return response()->json(['error' => $ex->getMessage()] ,$ex->getStatusCode());
        }
    }
    protected function updateUser() {
        try {
            $getUser = JWTAuth::parseToken()->toUser();
            if(!$getUser) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('full_name', 'phone', 'email', 'role');
            $rules = [
                'full_name' => 'required|string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
                'email' => 'required|email|max:255',
                'role' => 'required|string|max:255',

            ];
            $validator = Validator::make($request, $rules);
            if($validator->fails()) {
                return response()->json(['error' => 'validation error', 'message' => $validator->messages()], 500);
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
            // check weather the email exists before
            $check_email_existance = User::where('email', '=',$request['email'])->exists();
            if($check_email_existance && $request['email'] != $getUser->email) {
                return response()->json(['error' => 'The email has already been taken'], 400);
            }
            // check weather the phone exists before
            $check_phone_existance = User::where('phone', $phone_number)->exists();
            if($check_phone_existance && $phone_number != $getUser->phone) {
                return response()->json(['error' => 'The phone has already been taken.'], 400);
            }
            $oldUser = User::find($getUser->id);
            //$oldUser = User::find($id);
            
            if($oldUser instanceof User) {
                $oldUser->full_name = isset($request['full_name']) ? $request['full_name'] : $oldUser->full_name;
                $oldUser->phone = isset($request['phone']) ? $phone_number : $oldUser->phone;
                $oldUser->email = isset($request['email']) ? $request['email'] : $oldUser->email;

                $role = Role::where('name', '=', $request['role'])->first();
                if(!$role) {
                    return response()->json(['error' => 'role is not found'], 404);
                }
                $role_id = $role->id;
                $oldUser->roles()->detach($oldUser->roles->first());
                $oldUser->roles()->attach($role_id);
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
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['error' => 'token expired'], 401);
            }
            $request = request()->only('old_password', 'password', 'password_confirmation');
            // $requestNewPassword = request()->only('new_password', 'confirm_password');
            //old password validation
            $rule = [
                'old_password' => 'required|string|min:6',
                'password' => 'required|confirmed|string|min:6',
            ];
            $validate_password = Validator::make($request, $rule);
            if($validate_password->fails()) {
                return response()->json(['message' => 'password validation error', 'error' => $validate_password->messages()], 500);
            }

            $old_password = $request['old_password'];
            $encrypt_oldPassword = bcrypt($old_password);
            if(Hash::check($old_password, $user->password)) {
                $user->password = bcrypt($request['password']);
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
                return response()->json(['error' => 'token expired'], 401);
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
            return response()->json(['error' => 'token expired'], 401);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    protected function updateUserStatus($id) {
        try {
            $authUser = JWTAuth::parseToken()->toUser();
            if(!$authUser) {
                return response()->json(['error' => 'token expired'], 401);
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
            return response()->json(['token' => 'token expired'], 401);
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
                return response()->json(['error' => 'token expired'], 401);
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
}
