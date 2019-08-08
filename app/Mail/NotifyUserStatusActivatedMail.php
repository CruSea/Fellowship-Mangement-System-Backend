<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUserStatusActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $full_name;
    public $fellowship_name;
    public $role_name;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($full_name, $fellowship_name, $role_name)
    {
        $this->full_name = $full_name;
        $this->fellowship_name = $fellowship_name;
        $this->role_name = $role_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('Email.notifyUserStatusActivated')->with(['full_name' => $this->full_name, 'fellowship_name' => $this->fellowship_name, 'role_name' => $this->role_name]);
    }
}
