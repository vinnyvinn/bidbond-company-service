<?php

use Illuminate\Database\Seeder;
use App\PostalCode;

class PostalCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(getenv("APP_ENV") =='testing'){

             factory(PostalCode::class, 5)->create();
        }else{ 

             $count = PostalCode::count();

            if($count == 0){
                 DB::unprepared(file_get_contents(database_path('seeds/sql/postal_codes.sql')));
            }
        }
    }
}
