<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function sentMessage() {
        try {
            $request = request()->only('title', 'content', 'reciever_phone');
            $rule = [
                'message' => 'required|string|max:10000',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            $phone_rule = [
                'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
            ];
            $phone_validator = Validator::make($request, $phone_rule);
            if($phone_validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            $user = new User();
            $user = JWtAuth::parseToken()->toUser();
            if(!$user instanceof User) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            $getUser = $user->firstname;
            $status = 0;
            $sentMessage = new sentMessage([
                'message' => 'message',
                'sent_to' => 'phone',
                'status' => $status,
                'sent_by' => $getUser,
            ]);
            if($sentMessage->save()) {
                return response()->json(['info' => 'message sent seccessfully'], 200);
            }
            return response()->json(['error' => '!Ooops something went wrong'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => '!Ooops something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }

}
