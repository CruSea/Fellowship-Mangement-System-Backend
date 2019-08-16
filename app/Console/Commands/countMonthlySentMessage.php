<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SentMessage;
use Carbon\Carbon;
use App\User;
use App\countMessage;
use DateTime;

class countMonthlySentMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:countMonthlySentMessage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'number of last month successfully sent messages';

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
            $DiffM = Carbon::parse(date('Y-m-d'))->diffInMonths(Carbon::parse($sent_message->created_at));
            if($DiffM == 0) {
                $old_monthly_message = countMessage::where([['type', '=', 'monthly'], ['fellowship_id', '=', $sent_message->fellowship_id]])->first();
                if($old_monthly_message instanceof countMessage) {
                     $Diff_old_m = Carbon::parse(date('Y-m-d'))->diffInMonths(Carbon::parse($old_monthly_message->updated_at));
                     if($Diff_old_m != 0) {
                        $old_monthly_message->count = 0;
                        $old_monthly_message->update();
                     } else {
                        $old_monthly_message->count = $old_monthly_message->count + 1;
                         $old_monthly_message->updated_at = new DateTime();
                         $old_monthly_message->update();
                     }
                } else {
                    $count_monthly_message = new countMessage();
                    $count_monthly_message->count = 1;
                    $count_monthly_message->type = 'monthly';
                    $count_monthly_message->fellowship_id = $sent_message->fellowship_id;
                    $count_monthly_message->updated_at = new DateTime();
                    $count_monthly_message->save();
                }
            }
        }
    }
}
