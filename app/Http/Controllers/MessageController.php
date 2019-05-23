<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function sentMessage() {
        $request = request()->only('title', 'content', 'reciever_phone');
        $rule = [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:500',
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
        // send message here
    }

}
