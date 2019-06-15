<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    protected $fillable = ['message', 'sent_to', 'is_sent', 'is_delivered', 'sms_port_id', 'sent_by', 'created_at', 'updated_at'];
    protected $table = 'sent_messages';
}
