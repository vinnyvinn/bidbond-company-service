<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('crp')->unique();
            $table->string('email');
            $table->string('phone_number');
            $table->string('physical_address');
            $table->string('postal_address');
            $table->integer('postal_code_id')->unsigned()->index();
            $table->boolean('paid')->default(0);
            $table->string('company_unique_id')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('customerid')->nullable();
            $table->string('account')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('approval_status')->default('pending');
            $table->boolean('kyc_status')->default(true);
            $table->unsignedInteger('group_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    public function down()
    {

        Schema::dropIfExists('companies');
    }
}
