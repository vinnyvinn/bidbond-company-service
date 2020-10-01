<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerifyToCompanyDirectorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_director', function (Blueprint $table) {
            $table->renameColumn('active','verified');
            $table->string('verification_code')->after('company_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_director', function (Blueprint $table) {
            $table->renameColumn('verified','active');
            $table->dropColumn('verification_code');
        });
    }
}
