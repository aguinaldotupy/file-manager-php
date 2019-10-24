<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFileManagerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_manager', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('fileable');
            $table->string('type');
            $table->string('file_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('path_storage');
            $table->string('disk');
            $table->string('visibility');
            $table->string('thumbnail')->nullable(); 
            $table->string('tags')->nullable(); //Any Field 
            $table->text('observation')->nullable();
            $table->integer('order')->default(0); //Orderable by relevance
            $table->string('caption')->nullable(); //Title image for tag html
            $table->string('origem')->nullable(); //Route origem function
            $table->timestamp('expiration_date')->nullable();
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
        Schema::dropIfExists('file_manager');
    }
}
