<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SentMessage;
use Illuminate\Support\Str;

class countMessage extends Command
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
    protected $description = 'Command description';

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
        // dd('today');
        $sent_messages = SentMessage::all();
        $count_message = SentMessage::count();
        // $replace_created_at = [];
        $replace_created_at = Str::before('this is my', 'my');
        // if($count_message == 0) {

        // } else {
            // for($i = 0; $i < $count_message; $i++) {
                // $replace_created_at[$i] = Str::before('this is my', 'my');
            // }
        // }
        dd($replace_created_at);
        // $count_today_message = SentMessage::where('created_at', '=', )
    }
}
