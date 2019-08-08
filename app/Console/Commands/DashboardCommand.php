<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AlarmMessage;
use App\ScheduleMessage;
use App\TodayMessage;
use Carbon\Carbon;
class DashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:dashboardCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify scheduled and periodic message which will be with in 12 hours for leaders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // dd(date('Y-m-d'));
        $count = AlarmMessage::where('send_date', '=', date('Y-m-d'))->get()->count();
        if($count == 0) {
            // pass
        } else {
            $alarmMessages = AlarmMessage::where('send_date', '=', date('Y-m-d'))->get();
            foreach ($alarmMessages as $alarmMessage) {
                $remaining_hour = ((Carbon::parse((Carbon::parse(date('H:i')))))->diffInHours($alarmMessage->send_time, false));

                if($remaining_hour < 23 && $remaining_hour >= 0) {
                    $message = "'".$alarmMessage->message."' scheduled message will be sent after ".$remaining_hour." hours at ". $alarmMessage->send_time." for ". $alarmMessage->sent_to; 
                        $old_message = TodayMessage::where('alarm_message_id', '=', $alarmMessage->id)->first();
                    // }
                    // $old_message = TodayMessage::where('alarm_message_id', '=', $alarmMessage->id)->first();
                    if($old_message instanceof TodayMessage) {
                        $old_message->message = $message;
                        $old_message->alarm_message_id = $old_message->alarm_message_id;
                        $old_message->remaining_time = $remaining_hour;
                        $old_message->update();
                    } else {
                        $today_message = new TodayMessage();
                        $today_message->message = $message;
                        $today_message->alarm_message_id = $alarmMessage->id;
                        $today_message->remaining_time = $remaining_hour;
                        $today_message->save();
                    }
                }
            }
        }
        $periodic_daily = ScheduleMessage::where([['type', '=', 'daily'], ['end_date', '>=', date('Y-m-d')]])->get();
        if(count($periodic_daily) == 0) {
            // pass
        }
        else {
            foreach ($periodic_daily as $daily) {
                // dd('yes daily');
                $daily_remaining_hour = ((Carbon::parse((Carbon::parse(date('H:i')))))->diffInHours($daily->sent_time, false));
                if($daily_remaining_hour < 23 && $daily_remaining_hour >= 0) {
                    $daily_message = "'".$daily->message."' daily periodic message will be sent after ".$daily_remaining_hour." hours at ". $daily->sent_time." for ". $daily->sent_to; 
                    // $daily_old_message = TodayMessage::where('key', '=', $daily->key)->first();
                    $daily_old_message = TodayMessage::where('schedule_message_id', '=', $daily->id)->first();
                    if($daily_old_message instanceof TodayMessage) {
                        $daily_old_message->message = $daily_message;
                        $daily_old_message->schedule_message_id = $daily_old_message->schedule_message_id;
                        $daily_old_message->remaining_time = $daily_remaining_hour;
                        $daily_old_message->update();
                    } else {
                        $daily_today_message = new TodayMessage();
                        $daily_today_message->message = $daily_message;
                        $daily_today_message->schedule_message_id = $daily->id;
                        $daily_today_message->remaining_time = $daily_remaining_hour;
                        $daily_today_message->save();
                    }
                }
            }
        }
        $periodic_weekly = ScheduleMessage::where([['type', '=', 'weekly'], ['end_date', '>=', date('Y-m-d')],])->get();
        if(count($periodic_weekly) == 0) {
            // pass
        } else {
            foreach ($periodic_weekly as $weekly) {
                // $today_w = Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($weekly->start_date));
                $today_w = ((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInHours($weekly->start_date, false));
                if(($today_w) % 7 == 0 && $today_w >= 0) {
                    $weekly_remaining_hour = ((Carbon::parse((Carbon::parse(date('H:i')))))->diffInHours($weekly->sent_time, false));
                    if($weekly_remaining_hour < 23 && $weekly_remaining_hour >= 0) {
                        $weekly_message = "'".$weekly->message."' weekly periodic message will be sent after ".$weekly_remaining_hour." hours at ".$weekly->sent_time." for ". $weekly->sent_to;
                        $weekly_old_message = TodayMessage::where('schedule_message_id', '=', $weekly->id)->first();
                        if($weekly_old_message instanceof TodayMessage) {
                            $weekly_old_message->message = $weekly_message;
                            $weekly_old_message->schedule_message_id = $weekly_old_message->schedule_message_id;
                            $weekly_old_message->remaining_time = $weekly_remaining_hour;
                            $weekly_old_message->update();
                        } else {
                            $weekly_today_message = new TodayMessage();
                            $weekly_today_message->message = $weekly_message;
                            $weekly_today_message->schedule_message_id = $weekly->id;
                            $weekly_today_message->remaining_time = $weekly_remaining_hour;
                            $weekly_today_message->save();
                        }
                    }
                }
            }
        }
        // dd('something');
        $periodic_monthly = ScheduleMessage::where([['type' ,'=', 'monthly'], ['end_date', '>=', date('Y-m-d')]])->get();

        if(count($periodic_monthly) == 0) {
            // pass
        } 
        else {

            foreach ($periodic_monthly as $monthly) {

                $today_m = ((Carbon::parse((Carbon::parse(date('Y-m-d')))))->diffInHours($monthly->start_date, false));
                if(($today_m) % 28 == 0 && $today_m >= 0) {
                    $monthly_remaining_hour = ((Carbon::parse((Carbon::parse(date('H:i')))))->diffInHours($monthly->sent_time, false));
                    if($monthly_remaining_hour < 23 && $monthly_remaining_hour >= 0) {
                        $monthly_message = "'".$monthly->message."' monthly periodic message will be sent after ". $monthly_remaining_hour. " hours at ". $monthly->sent_time." for ". $monthly->sent_to;

                        $monthly_old_message = TodayMessage::where('schedule_message_id', '=', $monthly->id)->first();
                        if($monthly_old_message instanceof TodayMessage) {
                            // dd('something '.$monthly_old_message);
                            $monthly_old_message->message = $monthly_message;
                            $monthly_old_message->schedule_message_id = $monthly_old_message->schedule_message_id;
                            $monthly_old_message->remaining_time = $monthly_remaining_hour;
                            // dd('something '. $monthly_old_message->id)
                            $monthly_old_message->update();
                            // dd('something');
                        } else {
                            $monthly_today_message = new TodayMessage();
                            $monthly_today_message->message = $monthly_message;
                            $monthly_today_message->schedule_message_id = $monthly->id;
                            $monthly_today_message->remaining_time = $monthly_remaining_hour;
                            $monthly_today_message->save();
                        }
                    }
                }
            }
        }

        // delete sent message from dashboard
        TodayMessage::where('remaining_time', '=', 0)->delete();
    }
}
