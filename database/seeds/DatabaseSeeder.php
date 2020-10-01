<?php

use Illuminate\Database\Seeder;
use App\Author;
use App\PostalCode;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $this->call(PostalCodesSeeder::class);
    //    $this->call(CompanySeeder::class);
    }
}
