<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;


class CreateStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_status', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });


        $status_array = Config::get('app.mobiauth.status');
        for($x = 0; $x < count($status_array); $x++) {
            DB::table('auth_status')->insert(
                ['name' => $status_array[$x],
                  'created_at' => Carbon::now()->toDateTimeString(),
                  'updated_at'=> Carbon::now()->toDateTimeString()
                ]
            );
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_status');
    }
}
