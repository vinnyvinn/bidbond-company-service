<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyCache extends Model
{
    protected $fillable = [
        "registration_number", "registration_date", "postal_address", "status",
        "physical_address", "phone_number", "email", "business_name","kra_pin"
    ];

    public function directors()
    {
        return $this->hasMany('App\DirectorCache');
    }
}
