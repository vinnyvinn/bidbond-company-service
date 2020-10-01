<?php

use App\Services\GatewayService;
use App\Traits\SearchCompany;


class GatewayTest extends TestCase
{
    use SearchCompany;
    /** @test */
    public function can_fetch_phone_n_id_from_gateway_service()
    {
        $valid = $this->searchByPhoneNId("0712704404", "28194838");
        $this->assertTrue($valid);
    }
}
