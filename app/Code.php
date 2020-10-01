<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    protected $fillable = [
        'code_email', 'code_phone', 'count', 'phone_number', 'email'
    ];
}
