<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\PostGraduate;
use Carbon\Carbon;
use App\Notification;
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
        $post_graduates = Contact::whereDate('graduation_year', '<', date('Y-m-d'))->get();
        // update under graduate contact to post graduate
        for ($i = 0; $i < count($post_graduates); $i++) {
            Contact::where('graduation_year', '<', date('Y-m-d'))->update(['is_under_graduate' => 0, 'is_this_year_gc' => 0]);
        }

        // check whether contact is this year graduate
        $this_year_graduate = Contact::where('is_this_year_gc', '=', 0)->get();
        $contacts = Contact::all();

        foreach ($contacts as $contact) {
            $graduation_year = $contact->graduation_year;
            $today = Carbon::parse(date('Y-m-d'));
            $parse_graduation_year = Carbon::parse($graduation_year);
            $difference = $today->diffInDays($parse_graduation_year, false);
            if($difference < 380 && $difference > 0) {
                $contact->is_this_year_gc = 1;
                $contact->is_under_graduate = 1;
                $contact->update();
            }
        }
        foreach ($contacts as $under_graduate) {

            $graduationYear = $under_graduate->graduation_year;
            $to_day = Carbon::parse(date('Y-m-d'));
            $parse_graduationYear = Carbon::parse($graduationYear);
            $diff = $to_day->diffInDays($parse_graduationYear, false);
            // dd($graduationYear. ' '. $to_day);
            // dd($diff);
            if($diff > 380) {
                $under_graduate->is_under_graduate = 1;
                $under_graduate->is_this_year_gc = 0;
                $under_graduate->update();
            }
        }
    }
}
