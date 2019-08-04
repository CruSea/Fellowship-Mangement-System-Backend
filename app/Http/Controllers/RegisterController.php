<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\User;
use App\Role;
use App\UserRole;
use App\Fellowship;
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
                'specific_place' => 'string|max:255',
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13|unique:users',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required|string|min:6',

            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                return response()->json(['error' => 'validation error', 'message' => $validator->messages()], 500);
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
            if(strlen($phone_number) > 13 || strlen($phone_number) < 13) {
                return response()->json(['message' => 'validation error', 'error' => 'phone number length is not valid'], 400);
            }
            $check_phone_existance = User::where('phone', $phone_number)->exists();
            if($check_phone_existance) {
                return response()->json(['error' => 'The phone has already been taken'], 400);
            }

            $fellowship = new Fellowship();
            $fellowship->university_name = $request->input('university_name');
            $fellowship->university_city = $request->input('university_city');
            $fellowship->specific_place = $request->input('specific_place');
            if($fellowship->save()) {
                $user = new User();
                $user->full_name = $request->input('full_name');
                $user->phone = $phone_number;
                $user->email = $request->input('email');
                $user->fellowship_id = $fellowship->id;
                $user->password = bcrypt($request->input('password'));
                $user->remember_token = str_random(10);
                // $user->updated_at = new DateTime();
                if($user->save()) {
                    $role = Role::where('name', '=', 'viewer')->first();
                    if(!$role) {
                        $user_role = Role::find(4);
                        $user->attachRole($user_role);
                        return response()->json(['message' => 'successfully registered'], 201);
                    }
                    // $user_role = Role::find(4);
                    $user->attachRole($role);
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
}
