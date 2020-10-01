<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropVerifyColumnFromDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('directors', function (Blueprint $table) {
            $table->dropColumn('verify');
            $table->dropColumn('verified_crb');
            $table->dropColumn('last_crb');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('directors', function (Blueprint $table) {
            $table->boolean('verify')->default(0);
            $table->boolean('verified_crb')->default(0);
            $table->dateTime('last_crb')->nullable();
        });
    }
}
