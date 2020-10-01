<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;


class GatewayService
{

    use ConsumesExternalService;

    public $baseUri;

    public $secret;


    public function __construct()
    {
        $this->baseUri = config('services.gateway.base_uri');
        $this->secret = config('services.gateway.secret');
    }

    /**
     * Get company cost from the gateway
     * @param $phone
     * @param $id_number
     * @return string
     */
    public static function init(){
        return  new self();
    }
    public function getUserByPhoneNId($phone, $id_number)
    {
        return $this->performRequest('GET', "/user_details/$phone/$id_number");
    }

    public function registerAccount($company){
         return $this->performRequest('GET',"/create-account/{$company}");

    }

}
