<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\PostGraduate;
use Carbon\Carbon;
class UpdateContactToPostGraduate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateContact';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'contact will be updated from under graduate list to post graduate list automatically when the contact graduates';

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
        // check whether contact is graduated
        $post_graduates = Contact::whereDate('graduation_year', '<', Carbon::now())->get();
        // update under graduate contact to post graduate
        for ($i = 0; $i < count($post_graduates); $i++) {
            Contact::where('graduation_year', '<', Carbon::now())->update(['is_under_graduate' => 1]);
        }

        // check whether contact is this year graduate
        $this_year_graduate = Contact::where('is_this_year_gc', '=', 360)->get();
        $contacts = Contact::all();

        for($j = 1; $j < count($contacts) - 1; $j++) {
            $contact = Contact::find($j);
            $graduation_year = $contact->graduation_year;
            $today = Carbon::parse(Carbon::now());
            $parse_graduation_year = Carbon::parse($graduation_year);
            $difference = $parse_graduation_year->diffInDays($now_date, false);
            if($difference < 360) {
                $contact->is_this_year_gc = 1;
                $contact->save();
            }
            
        }
        // $contact = Contact::find(1);
        // $gra_year= $contact->graduation_year;
        // $parsed_date = Carbon::parse($gra_year);
        // $now_date = Carbon::parse(Carbon::now());
        // $sub = $parsed_date->diffInDays($now_date, false);
        // $count = count($post_graduates);
        // dd($sub);
    }
}
