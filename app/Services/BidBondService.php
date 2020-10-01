<?php

namespace App\Services;

use App\Traits\ConsumesExternalService;


class BidBondService
{

    use ConsumesExternalService;

    public $baseUri;

    public $secret;


    public function __construct()
    {
        $this->baseUri = config('services.bidbonds.base_uri');
        $this->secret = config('services.bidbonds.secret');
    }

    public function createCompany($data)
    {
        return $this->performRequest('POST', '/companies', $data);
    }

    public function updateCompany($data)
    {
        return $this->performRequest('PUT', '/companies', $data);
    }
}
