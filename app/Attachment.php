<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['name', 'user_id'];

    public function attachable()
    {
        return $this->morphTo();
    }
}
