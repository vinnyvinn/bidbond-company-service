<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{

  protected $fillable = [
    'name'
  ];

  public function companies()
  {
    return $this->hasMany('App\Company')->withPivot('company_id');
  }
}
