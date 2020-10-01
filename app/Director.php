<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Director extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['deleted_at'];

    public function companies()
    {
        return $this->belongsToMany('App\Company')->withPivot('verified', 'verification_code')->withTimestamps();
    }
}
