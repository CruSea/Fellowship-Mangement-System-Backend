<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    protected $fillable = ['message', 'sent_to', 'status', 'sent_by', 'created_at', 'updated_at'];
    protected $table = 'sent_messages';
}
