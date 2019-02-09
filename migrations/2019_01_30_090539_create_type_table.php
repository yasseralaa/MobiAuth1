<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_type', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('auth_status_id')->unsigned();
            $table->timestamps();

            $table->foreign('auth_status_id')
                ->references('id')->on('auth_status');
        });



        $types_array = Config::get('app.mobiauth.types');
        foreach($types_array as $type => $status) {
            DB::table('auth_type')->insert(
                [
                    'name' => $type,
                    'auth_status_id' => DB::table('auth_status')->where('name', $status)->first()->id,
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
        Schema::dropIfExists('auth_type');
    }
}
