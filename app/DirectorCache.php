<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DirectorCache extends Model
{

    protected $fillable = ['name', 'id_number', 'id_type', 'company_cache_id'];

    public function company()
    {
        return $this->belongsTo('App\CompanyCache')->withPivot('company_cache_id');
    }
}
