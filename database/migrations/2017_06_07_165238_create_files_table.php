<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('disk_name');
            $table->integer('size');
            $table->string('type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->index()->nullable();
            $table->string('attachment_id')->index()->nullable();
            $table->string('attachment_type')->index()->nullable();
            $table->boolean('is_public')->default(1);
            $table->boolean('order')->default(0);
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
        Schema::dropIfExists('files');
    }
}
