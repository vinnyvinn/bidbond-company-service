<?php

use Illuminate\Database\Seeder;
use App\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        factory(Company::class, 5)->create();
    }
}
