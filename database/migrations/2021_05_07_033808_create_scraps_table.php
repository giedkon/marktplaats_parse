<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scraps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('scrap_url');
            $table->string('title');
            $table->integer('year');
            $table->integer('mileage');
            $table->float('price');
            $table->string('make_model');
            $table->string('fuel');
            $table->string('body_type');
            $table->integer('views');
            $table->string('image_large')->nullable();
            $table->string('image_thumb')->nullable();
            $table->text('description');
            $table->ipAddress('scraper_ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scraps');
    }
}
