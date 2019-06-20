<?php

namespace App\Http\Controllers;

use App\User;
use App\Role;
use App\UserRole;
use App\Fellowship;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use DateTime;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    protected function signup(Request $request) {
        try {
            $rules = [
                'full_name' => 'required|string|max:255',
                'university_name' => 'required|string|max:255',
                'university_city' => 'required|string|max:255',
                'specific_place' => 'string|max:255'
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return response()->json(['error' => 'validation error', 'message' => $validator->messages()], 500);
            }
            // // check if the email is unique
            // if(DB::table('users')->where('email', $request->input('email'))->exists()) {
            //     return response()->json(['error' => 'Ooops! This Email is Occupied'],500);
            // }
            
            // // check if the phone number is unique
            // else if(DB::table('users')->where('phone', $request->input('phone'))->exists()) {
            //     return response()->json(['error' => 'Ooops! This Phone is already in the database']);
            // }
            /*
            Validate email address
            *
            */
            $email_rule = ['email' => 'required|email|max:255|unique:users'];
            $email_validator = Validator::make($request->all(), $email_rule);
            if($email_validator->fails()) {
                return response()->json(['error' => 'email is not valid'], 500);
            }

            /*
            Validate password
            *
            */
            $password_rule = ['password' => 'required|string|min:6'];
            $password_validator = Validator::make($request->all(), $password_rule);
            if($password_validator->fails()) {
                return response()->json(['error' => 'password is not valid (min character for password is 6)'], 500);
            }

            /*
            Validate phone number
            *
            */
            $phoneNo_rule = ['phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:users'];
            $phone_validator = Validator($request->all(), $phoneNo_rule);
            if($phone_validator->fails()) {
                return response()->json(['error' => 'phone number is not valid'], 500);
            }
            
            // add automatically new user id in user_role table
            //$user_role = new UserRole();
            
            $fellowship = new Fellowship();
            $fellowship->university_name = $request->input('university_name');
            $fellowship->university_city = $request->input('university_city');
            $fellowship->specific_place = $request->input('specific_place');
            if($fellowship->save()) {
                $user = new User();
                $user->full_name = $request->input('full_name');
                $user->phone = $request->input('phone');
                $user->email = $request->input('email');
                $user->fellowship_id = $fellowship->id;
                $user->password = bcrypt($request->input('password'));
                $user->remember_token = str_random(10);
                // $user->updated_at = new DateTime();
                if($user->save()) {
                    $user_role = Role::find(4);
                    $user->attachRole($user_role);
                    return response()->json(['message' => 'user registered successfully'], 201);
                }
                else {
                    $fellowship->delete();
                    return response()->json(['error' => 'something went wrong unable to register'], 500);
                }
            } else {
                return response()->json(['error' => 'Ooops! something went wrong'], 500);
            }
            
        } catch(Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
        
        
    }
    protected function users() {
        $users = User::all();
        return response()->json(['user' => $user], 200);
    }
}
