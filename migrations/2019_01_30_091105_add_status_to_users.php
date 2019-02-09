<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddStatusToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('auth_status_id')->unsigned()
                ->default(DB::table('auth_status')->where('name', Config::get('app.mobiauth.user')['default_status'])->first()->id);;


            $table->foreign('auth_status_id')
                ->references('id')->on('auth_status')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('auth_status_id', function (Blueprint $table) {
            //
        });
    }
}
