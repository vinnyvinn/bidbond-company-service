<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    public function companies()
    {
        return $this->hasMany('App\PostalCode');
    }
}
