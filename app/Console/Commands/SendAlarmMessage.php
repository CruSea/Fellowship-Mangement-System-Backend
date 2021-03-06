<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\AlarmMessage;
use App\Setting;
use App\SmsPort;
use App\Contact;
use App\ContactTeam;
use App\Fellowship;
use App\Event;
use App\ContactEvent;
use App\Team;
use App\Notification;
use Carbon\Carbon;
class SendAlarmMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sendAlarmMessage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send message which specified for specific time';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $negarit_api_url;

    public function __construct()
    {
        parent::__construct();
        $this->negarit_api_url = 'https://api.negarit.net/api/';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // dd(date('Y-m-d'));
        // dd(date('H:i'));
        // $setting = Setting::where('name', '=', 'API_KEY')->first();
        // if(!$setting) {
        //     return response()->json(['error' => 'API KEY is not found, please add API KEY frist'], 404);
        // }
        $alarm_now = AlarmMessage::where('send_date', '=', date('Y-m-d'))->get();
        $count_alarm = count($alarm_now);
        for($i = 0; $i < $count_alarm; $i++) {
            $alarm = $alarm_now[$i];
            $sms_port = SmsPort::find($alarm->sms_port_id);
            if(!$sms_port) {
                return response()->json(['error' => 'sms port is not found'], 404);
            }
            $api_key = $sms_port->api_key;
            $fellowship_id = $sms_port->fellowship_id;
            // check stting existance
            $setting = Setting::where([['value', '=', $api_key], ['fellowship_id', '=', $fellowship_id]])->first();
            if(!$setting) {
                return response()->json(['error' => 'API_KEY is not found'], 404);
            }
            if((Carbon::parse(date('H:i'))->diffInMinutes(Carbon::parse($alarm->send_time))) == 0) {
                if($alarm->phone != null) {
                    $contains_name = Str::contains($alarm->message, '{name}');
                    $replaceName = $alarm->message;
                    $contact = Contact::where([['phone', '=', $alarm->phone], ['fellowship_id', '=', $alarm->get_fellowship_id]])->first();
                    if($contact instanceof Contact) {
                        if($contains_name) {
                            $replaceName = Str::replaceArray('{name}', [$contact->full_name], $alarm->message);
                        }
                    }
                    $message_send_request = array();
                    $message_send_request['API_KEY'] = $setting->value;
                    $message_send_request['message'] = $replaceName;
                    $message_send_request['sent_to'] = $alarm->phone;
                    $message_send_request['campaign_id'] = $sms_port->negarit_campaign_id;
                    $negarit_response = \App\Http\Controllers\Controller::sendPostRequest($this->negarit_api_url, 
                            'api_request/sent_message?API_KEY?='.$setting->value, 
                            json_encode($message_send_request));
                    $decoded_response = json_decode($negarit_response);
                    if($decoded_response) {
                         $notification = new Notification();
                        $notification->notification = "scheduled message has been sent for ". $alarm->phone. " at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                        if(isset($decoded_response->status) && isset($decoded_response->sent_message)) {
                            $send_message = $decoded_response->sent_message;
                        }
                    } else {
                        $notification = new Notification();
                        $notification->notification = "Ooops! scheduled message is not sent for ". $alarm->phone. " at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                    }
                }
                if($alarm->team_id != null) {
                                        // dd('team');
                    $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                    $alarm->team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 1)->get();
                    if($alarm->for_under_graduate == 0) {
                        $contacts = Contact::whereIn('id', ContactTeam::where('team_id','=', 
                        $alarm->team_id)->select('contact_id')->get())->where('is_under_graduate', '=', 0)->get();
                    }
                    $team = Team::find($alarm->team_id);

                    if(count($contacts) == 0) {
                        return response()->json(['message' => 'member is not found '.$team->name .' team'], 404);
                    }
                    $contains_name = Str::contains($alarm->message, '{name}');
                    if($contains_name) {
                        for($j = 0; $j < count($contacts); $j++) {
                            $contact = $contacts[$j];
                            $replaceName = Str::replaceArray('{name}', [$contact->full_name], $alarm->message);
                            $insert[] = ['id' => $j+1, 'message' => $replaceName, 'phone' => $contact->phone];
                        }
                    } else {
                        for($j = 0; $j < count($contacts); $j++) {
                            $contact = $contacts[$j];
                            $insert[] = ['id' => $j+1, 'message' => $alarm->message, 'phone' => $contact->phone];
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
                    // dd('about here');
                    if($decoded_response) { 
                         $notification = new Notification();
                        $notification->notification = "scheduled message has been sent for ". $team->name. " team at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                        if(isset($decoded_response->status)) {
                        }
                        else {
                        }
                    } else {
                        $notification = new Notification();
                        $notification->notification = "Ooops! scheduled message is not sent for ". $team->name. " team at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                    } 
                }
                if($alarm->fellowship_id != null) {
                    // dd('hi man');
                    $decoded_value = json_decode($alarm->sent_by);
                    $fellowship_id = $decoded_value->fellowship_id;
                    $contacts = Contact::where([['fellowship_id', '=', $fellowship_id], ['is_under_graduate', '=', 1]])->get();
                    if($alarm->for_under_graduate == 0) {
                        $contacts = Contact::where([['fellowship_id', '=', $fellowship_id], ['is_under_graduate', '=', 0]])->get();
                    }
                    $fellowship = Fellowship::find($fellowship_id);
                    if(count($contacts) == 0) {
                        return response()->json(['message' => 'contact is not found in '. $fellowship->university_name. ' fellowship'], 404);
                    }
                    $contains_name = Str::contains($alarm->message, '{name}');
                    if($contains_name) {
                        for($k = 0; $k < count($contacts); $k++) {
                            $contact = $contacts[$k];
                            $replaceName = Str::replaceArray('{name}', [$contact->full_name], $alarm->message);
                            $insert[] = ['id' => $k+1, 'message' => $replaceName, 'phone' => $contact->phone];
                        }
                    } else {
                        for($k = 0; $k < count($contacts); $k++) {
                            $contact = $contacts[$k];
                            $insert[] = ['id' => $k+1, 'message' => $alarm->message, 'phone' => $contact->phone];
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
                         $notification = new Notification();
                        $notification->notification = "scheduled message has been sent for all fellowship members at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                        if(isset($decoded_response->status)) {
                        }
                        else {
                        }
                    } else {
                        $notification = new Notification();
                        $notification->notification = "Ooops! scheduled message is not sent for all fellowship members at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                    }
                }
                if($alarm->event_id != null) {
                    $contacts = Contact::whereIn('id', ContactEvent::where('event_id', '=', $alarm->event_id)->select('contact_id')->get())->get();
                    $event = Event::find($alarm->event_id);
                    if(count($contacts) == 0) {
                        return response()->json(['message' => 'contact is not found '. $event->event_name.' event'], 404);
                    }
                    $contains_name = Str::contains($alarm->message, '{name}');
                    if($contains_name) {
                        for($m = 0; $m < count($contacts); $m++) {
                            $contact = $contacts[$m];
                            $replaceName = Str::replaceArray('{name}', [$contact->full_name], $alarm->message);
                            $insert[] = ['id' => $m+1, 'message' => $replaceName, 'phone' => $contact->phone];
                        }
                    } else {
                        for($m = 0; $m < count($contacts); $m++) {
                            $contact = $contacts[$m];
                            $insert[] = ['id' => $m+1, 'message' => $alarm->message, 'phone' => $contact->phone];
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
                         $notification = new Notification();
                        $notification->notification = "scheduled message has been sent for ". $event->event_name. " event at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                        if(isset($decoded_response->status)) {
                        }
                        else {
                        }
                    } else {
                        $notification = new Notification();
                        $notification->notification = "Ooops! scheduled message is not sent for ". $event->event_name. " event at ". Carbon::now();
                        $notification->fellowship_id = $fellowship_id;
                        $notification->save();
                    }
                }
            }
        }
    }
}
