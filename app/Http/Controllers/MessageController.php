<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\User;
use App\TeamMessage;
use App\Contact;
use App\ContactTeam;
use JWTAuth;
use App\SentMessage;

class MessageController extends Controller
{
    public function sendContactMessage() {
        try {
            $request = request()->only('message', 'phone', 'port_name');
            $rule = [
                'message' => 'required|string|max:10000',
                'port_name' => 'required|string|max:255'
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
            $getUser = $user->full_name;

            $getSmsPortName = SmsPort::find($request['port_name']);
            $getSmsPortId = $getSmsPortName->id;
            $sentMessage = new SentMessage([
                'message' => $request['message'],
                'sent_to' => $request['phone'],
                'is_sent' => false,
                'is_delivered' => false,
                'sms_port_id' => $getSmsPortId,
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
    public function getContactMessage($id) {
        try {
            $getMessage = SentMessage::find($id);
            if(!$getMessage) {
                return response()->json(['message' => 'error found', 
                'error' => 'message is not found'], 404);
            }
            return response()->json(['message' => $getMessage], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 
            'error' => $ex->getMessage()], 500);
        }
    }
    public function getContactsMessages() {
        try{
            $contactMessage = SentMessage::orderBy('id', 'DESC')->paginate(5);
            $countMessages = DB::table('sent_messages')->count();
            if($countMessages == 0) {
                return response()->json(['message is not available'], 404);
            }
            return response()->json(['messages' => $contactMessage, 'count' => $countMessages], 200);
        } catch(Exception $x) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteContactMessage($id) {
        try {
            $sentMessage = SentMessage::find($id);
            if(!$sentMessage) {
                return response()->json(['error' => 'message is not available'], 404);
            }
            if($sentMessage->delete()) {
                return response()->json(['message' => 'message deleted successfully'], 200);
            }
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'message is not deleted'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendGroupMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => 'user is not authenticated'], 404);
            }
            $team_message = new TeamMessage();
            $request = request()->only('port_name', 'team', 'message');
            $rule = [
                'message' => 'required|string|max:1000000',
                'team' => 'required|string|max:250',
                'port_name' => 'required|string|max:250'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return resposne()->json(['message' => 'validation error', 'error' => 'values are not valid'], 500);
            }
            $team = DB::table('teams')->where('name', '=', $request['team'])->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
            }

            $getSmsPortName = SmsPort::find($request['port_name']);
            $getSmsPortId = $getSmsPortName->id;

            $team_id = $team->id;
            $team_message->message = $request['message'];
            $team_message->team_id = $team_id;
            $team_message->sent_by = $user->full_name;
            $team_message->save();
            $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
            $team_id)->select('contact_id')->get())->get();
            for($i = 0; $i < count($contacts); $i++) {
                $contact = $contacts[$i];
                $sent_message = new SentMessage([
                    'message' => $request['message'],
                    'sent_to' => $contact->phone,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $getSmsPortId,
                    'sent_by' => $user->full_name,
                ]);
                $sent_message->save();
            }
            return response()->json(['message' => 'message sent successfully'], 200);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }

}
