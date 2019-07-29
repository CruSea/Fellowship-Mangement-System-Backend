<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use App\Team;
use App\TeamMessage;
use App\Fellowship;
use App\FellowshipMessage;
use App\Event;
use App\EventMessage;
use App\Contact;
use App\ContactTeam;
use App\ContactEvent;
use JWTAuth;
use App\SentMessage;
use App\SmsPort;
use App\Setting;

class MessageController extends Controller
{
    protected $negarit_api_url;
    public function __construct() {
        $this->middleware('ability:,send-message', ['only' => ['sendContactMessage', 'sendTeamMessage']]);
        $this->middleware('ability:,get-message', ['only' => ['getContactMessage', 'getContactsMessages', 'getNegaritRecievedMessage']]);
        $this->middleware('ability:,delete-contact-message', ['only' => ['deleteContactMessage']]);
        $this->negarit_api_url = 'http://api.negarit.net/api/';
    }
    public function sendContactMessage() {
        try {
            $user = JWtAuth::parseToken()->toUser();
            if(!$user instanceof User) {
                return response()->json(['error' => 'user is not found'], 404);
            }
            $request = request()->only('message', 'sent_to', 'port_name');
            $rule = [
                'message' => 'required|string|min:1',
                'port_name' => 'required|string|max:255',
                'sent_to' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:13',
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }

            // $getSmsPortName = SmsPort::find($request['port_name']);
            $getSmsPortName = DB::table('sms_ports')->where('port_name', '=', $request['port_name'])->first();
            if(!$getSmsPortName) {
                return response()->json(['error' => 'sms port is not found', 
                    'message' => 'add sms port first'], 404);
            }
            $getSmsPortId = $getSmsPortName->id;

            $getSmsPort = SmsPort::find($getSmsPortId);
            if(!$getSmsPort) {
                return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
            }
            // get api key from setting table
            $setting = Setting::where('name', '=', 'API_KEY')->first();
            if(!$setting) {
                return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
            }
            
            $phone_number  = $request['sent_to'];
            $contact0 = Str::startsWith($request['sent_to'], '0');
            $contact9 = Str::startsWith($request['sent_to'], '9');
            $contact251 = Str::startsWith($request['sent_to'], '251');
            if($contact0) {
                $phone_number = Str::replaceArray("0", ["+251"], $request['sent_to']);
            }
            else if($contact9) {
                $phone_number = Str::replaceArray("9", ["+2519"], $request['sent_to']);
            }
            else if($contact251) {
                $phone_number = Str::replaceArray("251", ['+251'], $request['sent_to']);
            }

            $contains_name = Str::contains($request['message'], '{name}');
            $contact = Contact::where('phone', '=', $phone_number)->first();
            if($contact instanceof Contact) {
                if($contains_name) {
                    $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
                    $sentMessage = new SentMessage([
                        'message' => $replaceName,
                        'sent_to' => $contact->full_name,
                        'is_sent' => false,
                        'is_delivered' => false,
                        'sms_port_id' => $getSmsPortId,
                        'sent_by' => $user,
                    ]);
                } else {
                    $sentMessage = new SentMessage([
                        'message' => $request['message'],
                        'sent_to' => $contact->full_name,
                        'is_sent' => false,
                        'is_delivered' => false,
                        'sms_port_id' => $getSmsPortId,
                        'sent_by' => $user,
                    ]);
                }
            } else {
                $sentMessage = new SentMessage([
                    'message' => $request['message'],
                    'sent_to' => $phone_number,
                    'is_sent' => false,
                    'is_delivered' => false,
                    'sms_port_id' => $getSmsPortId,
                    'sent_by' => $user,
                ]);
            }

            if($sentMessage->save()) {
                
                $get_campaign_id = $getSmsPort->negarit_campaign_id;
                $get_api_key = $getSmsPort->negarit_sms_port_id;
                $get_message = $sentMessage->message;
                $get_phone = $phone_number;
                $get_sender = $sentMessage->sent_by;

                // to send a post request (message) for Negarit API 
                $message_send_request = array();
                $message_send_request['API_KEY'] = $setting->value;
                $message_send_request['message'] = $get_message;
                $message_send_request['sent_to'] = $get_phone;
                $message_send_request['campaign_id'] = $get_campaign_id;
                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                        'api_request/sent_message?API_KEY?='.$setting->value, 
                        json_encode($message_send_request));
                $decoded_response = json_decode($negarit_response);
                if($decoded_response) { 
                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                        $send_message = $decoded_response->sent_message;
                        $sentMessage->is_sent = true;
                        $sentMessage->is_delivered = true;
                        $sentMessage->update();
                        return response()->json(['message' => 'message sent successfully',
                        'sent message' => $send_message], 200);
                    }
                    return response()->json(['response' => $decoded_response], 500);
                }
                return response()->json(['sent message' => [], 'response' => $decoded_response], 500);
            }
            return response()->json(['message' => '!Ooops something went wrong', 'error' => 'message is not sent, please send again'], 500);
        } catch(Exception $ex) {
            return response()->json(['message' => '!Ooops something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getContactMessage($id) {
        try {
            $getMessage = SentMessage::find($id);
            if($getMessage instanceof SentMessage) {
                $getMessage->sent_by = json_decode($getMessage->sent_by);
                return response()->json(['message' => $getMessage], 200);
            }
            return response()->json(['message' => 'error found', 
            'error' => 'message is not found'], 404);
            
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 
            'error' => $ex->getMessage()], 500);
        }
    }
    public function getContactsMessages() {
        try{
            $contactMessage = SentMessage::paginate(10);
            $countMessages = SentMessage::count();
            if($countMessages == 0) {
                return response()->json(['message is not available'], 404);
            }
            for($i = 0; $i < $countMessages; $i++) {
                $contactMessage[$i]->sent_by = json_decode($contactMessage[$i]->sent_by);
            }
            return response()->json(['messages' => $contactMessage, 'number_of_messages' => $countMessages], 200);
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
    public function sendTeamMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => 'user is not authenticated'], 404);
            }
            $team_message = new TeamMessage();
            $request = request()->only('port_name', 'team', 'message');
            $rule = [
                'message' => 'required|string|min:1',
                'team' => 'required|string|max:250',
                'port_name' => 'required|string|max:250'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            $team = DB::table('teams')->where('name', '=', $request['team'])->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
            }

            $getSmsPortName = SmsPort::where('port_name', '=', $request['port_name'])->first();
            if(!$getSmsPortName) {
                return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
            }
            $getSmsPortId = $getSmsPortName->id;

            $team_id = $team->id;
            $team_message->message = $request['message'];
            $team_message->team_id = $team_id;
            $team_message->sent_by = $user;
            $team_message->under_graduate = true;
            $team_message->save();
            $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
            $team_id)->select('contact_id')->get())->get();

            if(count($contacts) == 0) {
                return response()->json(['message' => 'member is not found in '.$team->name. ' team'], 404);
            }

            // get phones that recieve the message and not recieve the message
            // $get_successfull_sent_phones = array();
            $setting = Setting::where('name', '=', 'API_KEY')->first();
            if(!$setting) {
                return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
            }
            $insert = [];
            $contains_name = Str::contains($request['message'], '{name}');
            if($contains_name) {
                for($i = 0; $i < count($contacts); $i++) {
                    $contact = $contacts[$i];
                    $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
                    // $under_graduate = Contact::where([['id', $contacts[$i]->id], ['is_under_graduate', 0]])->get();
                    if($contact->is_under_graduate) {
                        $sent_message = new SentMessage([
                            'message' => $replaceName,
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $replaceName,
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                }
            } else {
                for($i = 0; $i < count($contacts); $i++) {
                    $contact = $contacts[$i];
                    // $under_graduate = Contact::where([['id', $contacts[$i]->id], ['is_under_graduate', 0]])->get();
                    if($contact->is_under_graduate) {
                        $sent_message = new SentMessage([
                            'message' => $request['message'],
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $request['message'],
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                }
            }
            if($insert == []) {
                $team_message->delete();
                return response()->json(['message' => 'under graduate member is not found in '.$team->name. ' team'], 404);
            }
            $negarit_message_request = array();
            $negarit_message_request['API_KEY'] = $setting->value;
            $negarit_message_request['campaign_id'] = $getSmsPortName->negarit_campaign_id;
            $negarit_message_request['messages'] = $insert;

            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                'api_request/sent_multiple_messages', 
                json_encode($negarit_message_request));
            $decoded_response = json_decode($negarit_response);
            if($decoded_response) {
                if(isset($decoded_response->status)) {
                    $sent_message->is_sent = true;
                    $sent_message->is_delivered = true;
                    $sent_message->update();
                    return response()->json(['response' => $decoded_response], 200);
                }
                else {
                    return response()->json(['response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
            }

        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendPostGraduateTeamMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if(!$user) {
                return response()->json(['message' => 'authentication error', 'error' => 'user is not authenticated'], 404);
            }
            $team_message = new TeamMessage();
            $request = request()->only('port_name', 'team', 'message');
            $rule = [
                'message' => 'required|string|min:1',
                'team' => 'required|string|max:250',
                'port_name' => 'required|string|max:250'
            ];
            $validator = Validator::make($request, $rule);
            if($validator->fails()) {
                return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
            }
            $team = DB::table('teams')->where('name', '=', $request['team'])->first();
            if(!$team) {
                return response()->json(['message' => 'error found', 'error' => 'team is not found'], 404);
            }

            $getSmsPortName = SmsPort::where('port_name', '=', $request['port_name'])->first();
            if(!$getSmsPortName) {
                return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
            }
            $getSmsPortId = $getSmsPortName->id;

            $team_id = $team->id;
            $team_message->message = $request['message'];
            $team_message->team_id = $team_id;
            $team_message->sent_by = $user;
            $team_message->under_graduate = false;
            $team_message->save();
            $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
            $team_id)->select('contact_id')->get())->get();

            // get phones that recieve the message and not recieve the message
            // $get_successfull_sent_phones = array();
            $setting = Setting::where('name', '=', 'API_KEY')->first();
            if(!$setting) {
                return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
            }
            $insert = [];
            $contains_name = Str::contains($request['message'], '{name}');
            if($contains_name) {
                for($i = 0; $i < count($contacts); $i++) {
                    $contact = $contacts[$i];
                    $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
                    // $under_graduate = Contact::where([['id', $contacts[$i]->id], ['is_under_graduate', 0]])->get();
                    if(!$contact->is_under_graduate) {
                        $sent_message = new SentMessage([
                            'message' => $replaceName,
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $replaceName,
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                }
            } else {
                for($i = 0; $i < count($contacts); $i++) {
                    $contact = $contacts[$i];
                    // $under_graduate = Contact::where([['id', $contacts[$i]->id], ['is_under_graduate', 0]])->get();
                    if(!$contact->is_under_graduate) {
                        $sent_message = new SentMessage([
                            'message' => $request['message'],
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $request['message'],
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                }
            }
            if($insert == []) {
                $team_message->delete();
                return response()->json(['message' => 'post graduate member is not found in '.$team->name.' team'], 404);

            }
            $negarit_message_request = array();
            $negarit_message_request['API_KEY'] = $setting->value;
            $negarit_message_request['campaign_id'] = $getSmsPortName->negarit_campaign_id;
            $negarit_message_request['messages'] = $insert;

            $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                'api_request/sent_multiple_messages', 
                json_encode($negarit_message_request));
            $decoded_response = json_decode($negarit_response);
            if($decoded_response) {
                if(isset($decoded_response->status)) {
                    $sent_message->is_sent = true;
                    $sent_message->is_delivered = true;
                    $sent_message->update();
                    return response()->json(['response' => $decoded_response], 200);
                }
                else {
                    return response()->json(['response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
            }

        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getTeamMessage() {
        try {
            $user = JWtAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team_message = TeamMessage::where('under_graduate', '=', true)->paginate(10);
                $count_team_message = count($team_message);
                if($count_team_message == 0) {
                    return response()->json(['message' => 'empty team message', 'team message' => []], 404);
                }
                for($i = 0; $i < $count_team_message; $i++) {
                    $team = Team::find($team_message[$i]->team_id);
                    $team_message[$i]->sent_by = json_decode($team_message[$i]->sent_by);
                    $team_message[$i]->team_id = $team->name;
                }
                return response()->json(['team_message' => $team_message], 200);
            }
            else {
                return response()->json(['error' => 'token expired'], 401);
            }
            
        } catch(Exception $ex) {
            return response()->json(['messag' => 'Ooops! something went wrong', 
                'error' => $ex->getMessage()], 500);
        }
    }
    public function getPostGraduateTeamMessage() {
        try {
            $user = JWtAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team_message = TeamMessage::where('under_graduate', '=', false)->paginate(10);
                $count_team_message = count($team_message);
                if($count_team_message == 0) {
                    return response()->json(['message' => 'empty team message', 'team message' => []], 404);
                }
                for($i = 0; $i < $count_team_message; $i++) {
                    $team = Team::find($team_message[$i]->team_id);
                    $team_message[$i]->sent_by = json_decode($team_message[$i]->sent_by);
                    $team_message[$i]->team_id = $team->name;
                }
                return response()->json(['team_message' => $team_message], 200);
            }
            else {
                return response()->json(['error' => 'token expired'], 401);
            }
            
        } catch(Exception $ex) {
            return response()->json(['messag' => 'Ooops! something went wrong', 
                'error' => $ex->getMessage()], 500);
        }
    }
    public function deleteTeamMessage($id) {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $team_message = TeamMessage::find($id);
                if($team_message instanceof TeamMessage) {
                    if($team_message->delete()) {
                        return response()->json(['message' => 'team message deleted successfully'], 200);
                    }
                    return response()->json(['message' => 'Ooops! something went wrong', 'error' => 'team message is not deleted'], 500);
                }
                return response()->json(['error' => 'team message is not found'], 404);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendFellowshipMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $request = request()->only('port_name','message');
                $fellowship_message = new FellowshipMessage();
                $rule = [
                    'message' => 'required|string|min:1',
                    'port_name' => 'required|string|max:250'
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
                }
                $getSmsPortName = SmsPort::where('port_name', '=', $request['port_name'])->first();
                if(!$getSmsPortName) {
                    return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
                }
                $getSmsPortId = $getSmsPortName->id;

                $fellowship_id = $user->fellowship_id;

                $fellowship = Fellowship::find($fellowship_id);
                if(!$fellowship) {
                    return response()->json(['message' => "can't send a fellowship message", 'error' => 'fellowship is not found'], 404);
                }

                $fellowship_message->message = $request['message'];
                $fellowship_message->fellowship_name = $fellowship->university_name;
                $fellowship_message->sent_by = $user;
                $fellowship_message->under_graduate = true;
                $fellowship_message->save();
                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

                if(count($contacts) == 0) {
                    return response()->json(['message' => 'member is not found in '. $fellowship->university_name. ' fellowship'], 404);
                }

                $setting = Setting::where('name', '=', 'API_KEY')->first();
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $insert = [];
                $contains_name = Str::contains($request['message'], '{name}');
                if($contains_name) {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];
                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);

                        if($contact->is_under_graduate) {
                            $sent_message = new SentMessage([
                                'message' => $replaceName,
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            if(!$sent_message->save()) {
                                $sent_message = new SentMessage([
                                    'message' => $replaceName,
                                    'sent_to' => $contact->full_name,
                                    'is_sent' => false,
                                    'is_delivered' => false,
                                    'sms_port_id' => $getSmsPortId,
                                    'sent_by' => $user,
                                ]);
                                $sent_message->save();
                            }
                            $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                        }
                    }
                } else {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];

                        if($contact->is_under_graduate) {
                            $sent_message = new SentMessage([
                                'message' => $request['message'],
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            if(!$sent_message->save()) {
                                $sent_message = new SentMessage([
                                    'message' => $request['message'],
                                    'sent_to' => $contact->full_name,
                                    'is_sent' => false,
                                    'is_delivered' => false,
                                    'sms_port_id' => $getSmsPortId,
                                    'sent_by' => $user,
                                ]);
                                $sent_message->save();
                            }
                            $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                        }
                    }
                }
                if($insert == []) {
                    $fellowship_message->delete();
                    return response()->json(['message' => 'under graduate members are not found in this fellowship'], 404);
                }
                $negarit_message_request = array();
                $negarit_message_request['API_KEY'] = $setting->value;
                $negarit_message_request['campaign_id'] = $getSmsPortName->negarit_campaign_id;
                $negarit_message_request['messages'] = $insert;

                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                    'api_request/sent_multiple_messages', 
                    json_encode($negarit_message_request));
                $decoded_response = json_decode($negarit_response);
                if($decoded_response) {
                    if(isset($decoded_response->status)) {
                        $sent_message->is_sent = true;
                        $sent_message->is_delivered = true;
                        $sent_message->update();
                        return response()->json(['response' => $decoded_response], 200);
                    }
                    else {
                        return response()->json(['response' => $decoded_response], 500);
                    }
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendPostGraduateFellowshipMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $request = request()->only('port_name','message');
                $fellowship_message = new FellowshipMessage();
                $rule = [
                    'message' => 'required|string|min:1',
                    'port_name' => 'required|string|max:250'
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 400);
                }
                $getSmsPortName = SmsPort::where('port_name', '=', $request['port_name'])->first();
                if(!$getSmsPortName) {
                    return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
                }
                $getSmsPortId = $getSmsPortName->id;

                $fellowship_id = $user->fellowship_id;

                $fellowship = Fellowship::find($fellowship_id);
                if(!$fellowship) {
                    return response()->json(['message' => "can't send a fellowship message", 'error' => 'fellowship is not found'], 404);
                }

                $fellowship_message->message = $request['message'];
                $fellowship_message->fellowship_name = $fellowship->university_name;
                $fellowship_message->under_graduate = false;
                $fellowship_message->sent_by = $user;
                $fellowship_message->save();
                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();

                $setting = Setting::where('name', '=', 'API_KEY')->first();
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $insert = [];
                $contains_name = Str::contains($request['message'], '{name}');
                if($contains_name) {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];
                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
                        if(!$contact->is_under_graduate) {
                            $sent_message = new SentMessage([
                                'message' => $replaceName,
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            if(!$sent_message->save()) {
                                $sent_message = new SentMessage([
                                    'message' => $replaceName,
                                    'sent_to' => $contact->full_name,
                                    'is_sent' => false,
                                    'is_delivered' => false,
                                    'sms_port_id' => $getSmsPortId,
                                    'sent_by' => $user,
                                ]);
                                $sent_message->save();
                            }
                            $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                        }
                    }
                } else {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];

                        if(!$contact->is_under_graduate) {
                            $sent_message = new SentMessage([
                                'message' => $request['message'],
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            if(!$sent_message->save()) {
                                $sent_message = new SentMessage([
                                    'message' => $request['message'],
                                    'sent_to' => $contact->full_name,
                                    'is_sent' => false,
                                    'is_delivered' => false,
                                    'sms_port_id' => $getSmsPortId,
                                    'sent_by' => $user,
                                ]);
                                $sent_message->save();
                            }
                            $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                        }
                    }
                }
                if($insert == []) {
                    $fellowship_message->delete();
                    return response()->json(['message' => 'post graduate members are not found in this fellowship'], 404);
                }

                $negarit_message_request = array();
                $negarit_message_request['API_KEY'] = $setting->value;
                $negarit_message_request['campaign_id'] = $getSmsPortName->negarit_campaign_id;
                $negarit_message_request['messages'] = $insert;

                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                    'api_request/sent_multiple_messages', 
                    json_encode($negarit_message_request));
                $decoded_response = json_decode($negarit_response);
                if($decoded_response) {
                    if(isset($decoded_response->status)) {
                        $sent_message->is_sent = true;
                        $sent_message->is_delivered = true;
                        $sent_message->update();
                        return response()->json(['response' => $decoded_response], 200);
                    }
                    else {
                        return response()->json(['response' => $decoded_response], 500);
                    }
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getFellowshipMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $fellowship_message = FellowshipMessage::where('under_graduate', '=', true)->paginate(10);
                $count_message = count($fellowship_message);
                if($count_message == 0) {
                    return response()->json(['message' => 'empty fellowship message'], 404);
                }
                for($i = 0; $i < $count_message; $i++) {
                    $fellowship_message[$i]->sent_by = json_decode($fellowship_message[$i]->sent_by);
                }

                return response()->json(['message' => $fellowship_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getPostGraduateFellowshipMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $fellowship_message = FellowshipMessage::where('under_graduate', '=', false)->paginate(10);
                $count_message = count($fellowship_message);
                if($count_message == 0) {
                    return response()->json(['message' => 'empty fellowship message'], 404);
                }
                for($i = 0; $i < $count_message; $i++) {
                    $fellowship_message[$i]->sent_by = json_decode($fellowship_message[$i]->sent_by);
                }
                return response()->json(['message' => $fellowship_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function sendEventMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $event_message = new EventMessage();
                $request = request()->only('port_name', 'event', 'message');
                $rule = [
                    'message' => 'required|string|min:1',
                    'event' => 'required|string|max:250',
                    'port_name' => 'required|string|max:250'
                ];
                $validator = Validator::make($request, $rule);
                if($validator->fails()) {
                    return response()->json(['message' => 'validation error', 'error' => $validator->messages()], 500);
                }
                $event = Event::where('event_name', '=', $request['event'])->first();
                if(!$event) {
                    return response()->json(['error' => 'event is not found'], 404);
                }

                $getSmsPortName = SmsPort::where('port_name', '=', $request['port_name'])->first();
                if(!$getSmsPortName) {
                    return response()->json(['message' => 'error found', 'error' => 'sms port is not found'], 404);
                }
                $getSmsPortId = $getSmsPortName->id;

                $event_id = $event->id;
                $event_message->message = $request['message'];
                $event_message->event_id = $event_id;
                $event_message->sent_by = $user;
                $event_message->save();

                $contacts = Contact::whereIn('id', ContactEvent::where('event_id','=', 
                    $event_id)->select('contact_id')->get())->get();

                $setting = Setting::where('name', '=', 'API_KEY')->first();
                if(!$setting) {
                    return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'], 404);
                }
                $insert = [];
                $contains_name = Str::contains($request['message'], '{name}');
                if($contains_name) {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];
                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $request['message']);
                        $sent_message = new SentMessage([
                            'message' => $replaceName,
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $replaceName,
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                } else {
                    for($i = 0; $i < count($contacts); $i++) {
                        $contact = $contacts[$i];

                        $sent_message = new SentMessage([
                            'message' => $request['message'],
                            'sent_to' => $contact->full_name,
                            'is_sent' => false,
                            'is_delivered' => false,
                            'sms_port_id' => $getSmsPortId,
                            'sent_by' => $user,
                        ]);
                        if(!$sent_message->save()) {
                            $sent_message = new SentMessage([
                                'message' => $request['message'],
                                'sent_to' => $contact->full_name,
                                'is_sent' => false,
                                'is_delivered' => false,
                                'sms_port_id' => $getSmsPortId,
                                'sent_by' => $user,
                            ]);
                            $sent_message->save();
                        }
                        $insert[] = ['id' => $i+1, 'message' => $sent_message->message, 'phone' => $contact->phone];
                    }
                }
                if($insert == []) {
                    $event_message->delete();
                    return response()->json(['message' => 'member is not found in '.$event->event_name. ' event'], 404);
                }
                $negarit_message_request = array();
                $negarit_message_request['API_KEY'] = $setting->value;
                $negarit_message_request['campaign_id'] = $getSmsPortName->negarit_campaign_id;
                $negarit_message_request['messages'] = $insert;

                $negarit_response = $this->sendPostRequest($this->negarit_api_url, 
                    'api_request/sent_multiple_messages', 
                    json_encode($negarit_message_request));
                $decoded_response = json_decode($negarit_response);
                if($decoded_response) {
                    if(isset($decoded_response->status)) {
                        $sent_message->is_sent = true;
                        $sent_message->is_delivered = true;
                        $sent_message->update();
                        return response()->json(['response' => $decoded_response], 200);
                    }
                    else {
                        return response()->json(['response' => $decoded_response], 500);
                    }
                } else {
                    return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decoded_response], 500);
                }
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getEventMessage() {
        try {
            $user = JWTAuth::parseToken()->toUser();
            if($user instanceof User) {
                $event_message = EventMessage::paginate(10);
                $count_message = EventMessage::count();
                if($count_message == 0) {
                    return response()->json(['response' => 'empty event message'], 404);
                }
                for($i = 0; $i < $count_message; $i++) {
                    $event = Event::find($event_message[$i]->event_id);
                    $event_message[$i]->sent_by = json_decode($event_message[$i]->sent_by);
                    $event_message[$i]->event_id = $event->event_name;
                }
                return response()->json(['message' => $event_message], 200);
            } else {
                return response()->json(['error' => 'token expired'], 401);
            }
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
    public function getNegaritRecievedMessage() {
        try {
            $setting = Setting::where('name', '=', 'API_KEY')->first();
            if($setting instanceof Setting) {
                $API_KEY = $setting->value;
                $negarit_response = $this->sendGetRequest($this->negarit_api_url,
                    'api_request/received_messages?API_KEY='.$API_KEY);
                $decode_negarit_response = json_decode($negarit_response);
                if($decode_negarit_response) {
                    // if(isset($decode_negarit_response)) {
                    //     return response()->json(['messages' => $decode_negarit_response], 200);
                    // }
                    if(isset($decode_negarit_response->status) && isset($decode_negarit_response->received_messages)) {
                                $received_messages = $decode_negarit_response->received_messages;
                                return response()->json(['status'=> true, 'received_messages'=> $received_messages],200);
                            }
                     // ????????????????????????????????????????????????
                    //**"""""""""""""""something to do here"""""""""*******************************/
                }
                return response()->json(['message' => 'Ooops! something went wrong', 'response' => $decode_negarit_response], 500);
            }
            return response()->json(['message' => '404 error found', 'error' => 'Api Key is not found'],404);
        } catch(Exception $ex) {
            return response()->json(['message' => 'Ooops! something went wrong', 'error' => $ex->getMessage()], 500);
        }
    }
}
