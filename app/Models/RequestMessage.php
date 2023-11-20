<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestMessage extends Model
{
    protected $fillable = ['destination_id', 'source_id', 'encrypted_message', 'replied'];
}
