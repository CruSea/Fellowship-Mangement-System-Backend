<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        // Commnads\updateContactToPostGraduate::class,
        'App\Console\commands\UpdateContactToPostGraduate',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('command:updateContact')
                ->everyMinute();
        // $schedule->call(function () {
        //     DB::table('contacts')->whereDate('graduation_year', '<', date('Y-m-d'))->delete();
            // DB::table('post_graduates_contact')->whereDate('graduation_year', '<', date('Y-m-d'))->
            // update(['full_name' => 'Eyosias Desta Langena']);
        // })->daily();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
