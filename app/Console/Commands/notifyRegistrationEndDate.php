<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\RegistrationKey;
use Carbon\Carbon;
use App\Notification;

class notifyRegistrationEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:registrationEndDate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify for admins registration end date';

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
        $end_dates = RegistrationKey::where('registration_end_date', '=', date('Y-m-d'))->get();
        // dd($end_dates);
        foreach ($end_dates as $end_date) {
            $message = 'registration key \''. $end_date->registration_key .'\' for contact update has been expired';
            if($end_date->type == 'event_registration') {
                $message = 'registration key \''. $end_date->registration_key .'\' for '. $end_date->event.' event registration has been expired';
            }
            $notification = new Notification();
            $notification->notification = $message;
            $notification->fellowship_id = $end_date->fellowship_id;
            $notification->save();
        }
    }
}
