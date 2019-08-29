<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SmsRegisteredMembers;

class DeleteExpiredSmsRegisteredMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deleteExpiredSmsRegisteredMembers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete members from sms_registered_members table when registration_key end date is today';

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
        $registered_members = SmsRegisteredMembers::where('registration_end_date', '=', date('Y-m-d'))->delete();
    }
}
