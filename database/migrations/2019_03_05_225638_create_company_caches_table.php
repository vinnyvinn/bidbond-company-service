<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyCachesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_caches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('business_name');
            $table->string('registration_number');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('physical_address')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('kra_pin')->nullable();
            $table->date('registration_date');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_caches');
    }
}
