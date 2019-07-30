<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Fellowship;
use App\Notification;
use JWTAuth;
class FellowshipController extends Controller
{
    public function show() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $fellowship_id = $user->fellowship_id;
                $fellowship = Fellowship::find($fellowship_id);
                if($fellowship instanceof Fellowship) {
                    return response()->json(['fellowship' => $fellowship], 200);
                } else {
                    return response()->json(['error' => 'Ooops! something went wrong, fellowship is not found'], 404);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
        }
    }
    public function update() {
    	try {
    		$user = JWTAuth::parseToken()->toUser();
    		if($user instanceof User) {
    			$request = request()->only('university_name', 'university_city', 'specific_place');
    			$rule = [
    				'university_name' => 'required|string|min:1',
    				'university_city' => 'required|string|min:1',
    				'specific_place' => 'string|min:1|nullable',
    			];
    			$validator = Validator::make($request, $rule);
    			if($validator->fails()) {
    				return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
    			}
                $notification = new Notification();
                $fellowship_id = $user->fellowship_id;
    			$fellowship = Fellowship::find($fellowship_id);
    			$fellowship->university_name = $request['university_name'];
    			$fellowship->university_city = $request['university_city'];
    			$fellowship->specific_place = $request['specific_place'];

    			if($fellowship->update()) {
                    $notification->notification = "Fellowship profile has been updated by ".$user->full_name. " on ". date('Y-m-d');
                    $notification->save();
    				return response()->json(['message' => 'fellowship updated successfully'], 200);
    			} else {
    				return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'fellowship is not updated'], 500);
    			}
    		} else {
    			return response()->json(['error' => 'token expired'], 401);
    		}
    	} catch(Exception $ex) {
    		return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], $ex->getStatusCode());
    	}
    }
}
