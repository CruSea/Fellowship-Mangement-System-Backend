<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SentMessage;
use Carbon\Carbon;
use App\User;
use App\countMessage;
use DateTime;

class countMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:countMessage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count today successfully sent messages';

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
        $sent_messages = SentMessage::where('is_sent', '=', true)->get();

        foreach ($sent_messages as $sent_message) {
            // today sent message
            $Diff = Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($sent_message->created_at));
            if($Diff == 0) {
                $old_today_message = countMessage::where([['type', '=', 'today'], ['fellowship_id', '=', $sent_message->fellowship_id]])->first();
                if($old_today_message instanceof countMessage) {
                    $Diff_old = Carbon::parse(date('Y-m-d'))->diffInDays(Carbon::parse($old_today_message->updated_at));
                    if($Diff_old != 0) {
                        $old_today_message->count = 0;
                        $old_today_message->update();
                    } else {
                        $old_today_message->count = $old_today_message->count + 1;
                        $old_today_message->updated_at = new DateTime();
                        $old_today_message->update();
                    }
                } else {
                    $count_today_message = new countMessage();
                    $count_today_message->count = 1;
                    $count_today_message->type = 'today';
                    $count_today_message->fellowship_id = $sent_message->fellowship_id;
                    $count_today_message->updated_at = new DateTime();
                    $count_today_message->save();
                }   
            }
        }
    }
}
