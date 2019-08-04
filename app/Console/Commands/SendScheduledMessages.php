<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\ScheduleMessage;
use App\Setting;
use App\SmsPort;
use App\Contact;
use App\ContactTeam;
use App\Event;
use App\ContactEvent;
use App\Team;
use App\Fellowship; 
use App\Notification;
use Carbon\Carbon;
// use App\Http\Controllers\Controller;

class SendScheduledMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string

     */
    protected $signature = 'command:sendScheduledMessages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send messages which are scheduled for specific time with in the given interval like daily, weekly, monthly, yearly';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $negarit_api_url;
    public function __construct()
    {
        parent::__construct();
        $this->negarit_api_url = 'http://api.negarit.net/api/';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // dd(date('H:i'));
        $setting = Setting::where('name', '=', 'API_KEY')->first();
        if(!$setting) {
            return response()->json(['error' => 'API KEY is not found, please add API KEY frist'], 404);
        }
        
        $daily_scheduled_message = ScheduleMessage::where('type', '=', 'daily')->get();
        $count_daily_message = count($daily_scheduled_message);
        if($count_daily_message == 0) {
            //
        } else {
            for($countD = 0; $countD < $count_daily_message; $countD++) {
                $daily = $daily_scheduled_message[$countD];
                $sms_port = SmsPort::find($daily->sms_port_id);
                if(!$sms_port) {
                    return response()->json(['error' => 'sms port is not found'], 404);
                }
                if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($daily->end_date, false) >= 0) {
                    $DiffInDate = Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($daily->start_date));
                    if(($DiffInDate) % 1 == 0 && $DiffInDate >= 0) {
                        if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($daily->sent_time))) == 0) {
                            if($daily->sent_to != null) {
                                // dd('sent to working '. $daily->sent_to);
                                $contains_name = Str::contains($daily->message, '{name}');
                                $replaceName = $daily->message;
                                $contact = Contact::where([['phone', '=', $daily->sent_to], ['fellowship_id', '=', $daily->get_fellowship_id]])->first();
                                if($contact instanceof Contact) {
                                    if($contains_name) {
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $daily->message);
                                    }
                                }
                                $message_send_request = array();
                                $message_send_request['API_KEY'] = $setting->value;
                                $message_send_request['message'] = $replaceName;
                                $message_send_request['sent_to'] = $daily->sent_to;
                                $message_send_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                        'api_request/sent_message?API_KEY?='.$setting->value, 
                                        json_encode($message_send_request));
                                $decoded_response = json_decode($negarit_response);
                                dd('message sent to contact successfullyffffffffff');
                                if($decoded_response) { 
                                    dd('message sent to contact successfullywwwwwwww');
                                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                        dd('message sent to contact successfully');
                                        $send_message = $decoded_response->sent_message;

                                    }
                                }
                            }
                            if($daily->team_id != null) {
                                // dd('team works '. $daily->team_id);
                                $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                                $daily->team_id)->select('contact_id')->get())->get();
                                $team = Team::find($daily->team_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $team->name. ' team'], 404);
                                }
                                $contains_name = Str::contains($daily->message, '{name}');
                                if($contains_name) {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $daily->message);
                                        $insert[] = ['id' => $j+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $insert[] = ['id' => $j+1, 'message' => $daily->message, 'phone' => $contact->phone];
                                    }
                                }

                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                // dd('message sent to the team successfully five');
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                            if($daily->fellowship_id != null) {
                                $decoded_value = json_decode($daily->sent_by);
                                $fellowship_id = $decoded_value->fellowship_id;
                                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();
                                $fellowship = Fellowship::find($fellowship_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $fellowship->university_name. ' fellowship'], 404);
                                }
                                $contains_name = Str::contains($daily->message, '{name}');
                                if($contains_name) {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $daily->message);
                                        $insert[] = ['id' => $m+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $insert[] = ['id' => $m+1, 'message' => $daily->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                            if($daily->event_id != null) {
                                $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $daily->event_id)->select('contact_id')->get())->get();
                                $event = Event::find($daily->event_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $event->event_name.' event'], 404);
                                }
                                $contains_name = Str::contains($daily->message, '{name}');
                                if($contains_name) {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $daily->message);
                                        $insert[] = ['id' => $m+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                }
                                else {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $insert[] = ['id' => $m+1, 'message' => $daily->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                        dd('message sent to event');
                                    }
                                }
                            }
                        }
                    }
                } else if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($daily->end_date, false) == -1) {
                    if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($daily->sent_time))) == 0) {
                        $notification = new Notification();
                        $notification->notification = "daily scheduled '".$daily->message. "' message has expired. end date was ". $daily->end_date;
                        $notification->save();
                    }
                }
            }
        }

        $weekly_scheduled_message = ScheduleMessage::where('type', '=', 'weekly')->get();
        
        $count = count($weekly_scheduled_message);
        if($count == 0) {
            // 
        } else {
            // dd(date('H:i'));
            for($i = 0; $i < count($weekly_scheduled_message); $i++) {
                $weekly = $weekly_scheduled_message[$i];
                $team_id = $weekly->team_id;
                $sms_port = SmsPort::find($weekly->sms_port_id);
                if(!$sms_port) {
                    return response()->json(['error' => 'sms port is not found'], 404);
                }
                if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($weekly->end_date, false) >= 0) {
                    $DiffInDate = Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($weekly->start_date));
                    if(($DiffInDate) % 7 == 0 && $DiffInDate >= 0) {
                        if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($weekly->sent_time))) == 0) {
                            if($weekly->sent_to != null) {
                                $contains_name = Str::contains($weekly->message, '{name}');
                                $replaceName = $weekly->message;
                                $contact = Contact::where([['phone', '=', $weekly->sent_to], ['fellowship_id', '=', $weekly->get_fellowship_id]])->first();
                                if($contact instanceof Contact) {
                                    if($contains_name) {
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $weekly->message);
                                    }
                                }
                                $message_send_request = array();
                                $message_send_request['API_KEY'] = $setting->value;
                                $message_send_request['message'] = $replaceName;
                                $message_send_request['sent_to'] = $weekly->sent_to;
                                $message_send_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                        'api_request/sent_message?API_KEY?='.$setting->value,
                                        json_encode($message_send_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                        $send_message = $decoded_response->sent_message;
                                    }
                                }
                            }
                            if($weekly->team_id != null) {
                                $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                                $team_id)->select('contact_id')->get())->get();
                                $team = Team::find($weekly->team_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $team->name. ' team'], 404);
                                }
                                $contains_name = Str::contains($weekly->message, '{name}');
                                if($contains_name) {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $weekly->message);
                                        $insert[] = ['id' => $j+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                }
                                else {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $insert[] = ['id' => $j+1, 'message' => $weekly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                } 
                            }
                            if($weekly->fellowship_id != null) {
                                $decoded_value = json_decode($weekly->sent_by);
                                $fellowship_id = $decoded_value->fellowship_id;
                                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();
                                $fellowship = Fellowship::find($fellowship_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '.$fellowship->university_name. ' fellowship'], 404);
                                }
                                $contains_name = Str::contains($weekly->message, '{name}');
                                if($contains_name) {
                                    for($k = 0; $k < count($contacts); $k++) {
                                        $contact = $contacts[$k];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $weekly->message);
                                        $insert[] = ['id' => $k+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($k = 0; $k < count($contacts); $k++) {
                                        $contact = $contacts[$k];
                                        $insert[] = ['id' => $k+1, 'message' => $weekly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                            if($weekly->event_id != null) {
                                $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $weekly->event_id)->select('contact_id')->get())->get();
                                $event = Event::find($weekly->event_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $event->event_name.' event'], 404);
                                }
                                $contains_name = Str::contains($weekly->message, '{name}');
                                if($contains_name) {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $weekly->message);
                                        $insert[] = ['id' => $m+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $insert[] = ['id' => $m+1, 'message' => $weekly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);
                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                        }
                    }
                } 
                else if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($weekly->end_date, false) == -1) {
                    if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($weekly->sent_time))) == 0) {
                        $notification = new Notification();
                        $notification->notification = "weekly scheduled '".$weekly->message. "' message has expired. end date was ". $weekly->end_date;
                        $notification->save();
                    } else {dd(date('H:i'));}
                } 
            }
        }

        $monthly_scheduled_message = ScheduleMessage::where('type', '=', 'monthly')->get();

        $count_montyly_message = count($monthly_scheduled_message);
        if($count_montyly_message == 0) {

        } else {
            for($i = 0; $i < $count_montyly_message; $i++) {
                $monthly = $monthly_scheduled_message[$i];
                $sms_port = SmsPort::find($monthly->sms_port_id);
                if(!$sms_port) {
                    return response()->json(['error' => 'sms port is not found'], 404);
                }
                if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($monthly->end_date, false) >= 0) {
                    if((Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($monthly->start_date))) % 28 == 0) {
                        if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($monthly->sent_time))) == 0) {
                            if($monthly->sent_to != null) {
                                $contains_name = Str::contains($monthly->message, '{name}');
                                $replaceName = $monthly->message;
                                $contact = Contact::where([['phone', '=', $monthly->sent_to], ['fellowship_id', '=', $monthly->get_fellowship_id]])->first();
                                if($contact instanceof Contact) {
                                    if($contains_name) {
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $monthly->message);
                                    }
                                }
                                $message_send_request = array();
                                $message_send_request['API_KEY'] = $setting->value;
                                $message_send_request['message'] = $replaceName;
                                $message_send_request['sent_to'] = $monthly->sent_to;
                                $message_send_request['campaign_id'] = $sms_port->negarit_campaign_id;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                        'api_request/sent_message?API_KEY?='.$setting->value,
                                        json_encode($message_send_request));
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                                        $send_message = $decoded_response->sent_message;
                                    }
                                }
                            }
                            if($monthly->team_id != null) {
                                $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                                $monthly->team_id)->select('contact_id')->get())->get();
                                $team = Team::find($monthly->team_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $team->name. ' team'], 404);
                                }
                                $contains_name = Str::contains($monthly->message, '{name}');
                                if($contains_name) {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $monthly->message);
                                        $insert[] = ['id' => $j+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($j = 0; $j < count($contacts); $j++) {
                                        $contact = $contacts[$j];
                                        $insert[] = ['id' => $j+1, 'message' => $monthly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                    'api_request/sent_multiple_messages', 
                                    json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);
                                    
                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                            if($monthly->fellowship_id != null) {
                                $decoded_value = json_decode($monthly->sent_by);
                                $fellowship_id = $decoded_value->fellowship_id;
                                $contacts = Contact::where('fellowship_id', '=', $fellowship_id)->get();
                                $fellowship = Fellowship::find($fellowship_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '.$fellowship->university_name.' fellowship'], 404);
                                }
                                $contains_name = Str::contains($monthly->message, '{name}');
                                if($contains_name) {
                                    for($k = 0; $k < count($contacts); $k++) {
                                        $contact = $contacts[$k];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $monthly->message);
                                        $insert[] = ['id' => $k+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($k = 0; $k < count($contacts); $k++) {
                                        $contact = $contacts[$k];
                                        $insert[] = ['id' => $k+1, 'message' => $monthly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                            if($monthly->event_id != null) {
                                $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $monthly->event_id)->select('contact_id')->get())->get();
                                $event = Event::find($monthly->event_id);
                                if(count($contacts) == 0) {
                                    return response()->json(['message' => 'member is not found in '. $event->event_name.' event'], 404);
                                }
                                $contains_name = Str::contains($monthly->message, '{name}');
                                if($contains_name) {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $replaceName = Str::replaceArray('{name}', [$contact->full_name], $monthly->message);
                                        $insert[] = ['id' => $m+1, 'message' => $replaceName, 'phone' => $contact->phone];
                                    }
                                } else {
                                    for($m = 0; $m < count($contacts); $m++) {
                                        $contact = $contacts[$m];
                                        $insert[] = ['id' => $m+1, 'message' => $monthly->message, 'phone' => $contact->phone];
                                    }
                                }
                                $negarit_message_request = array();
                                $negarit_message_request['API_KEY'] = $setting->value;
                                $negarit_message_request['campaign_id'] = $sms_port->negarit_campaign_id;
                                $negarit_message_request['messages'] = $insert;

                                $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                                'api_request/sent_multiple_messages', 
                                json_encode($negarit_message_request));
                                $decoded_response = json_decode($negarit_response);

                                if($decoded_response) { 
                                    if(isset($decoded_response->status)) {
                                    }
                                }
                            }
                        }
                    }
                } else if((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInDays($monthly->end_date, false) == -1) {
                    if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($monthly->sent_time))) == 0) {
                        $notification = new Notification();
                        $notification->notification = "monthly scheduled '".$monthly->message. "' message has expired. end date was ". $monthly->end_date;
                        $notification->save();
                    }
                } 
            }
        }
    }
}
