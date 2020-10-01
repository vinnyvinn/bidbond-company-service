<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanySearchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_searches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('registration_number');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('company_searches');
    }
}
