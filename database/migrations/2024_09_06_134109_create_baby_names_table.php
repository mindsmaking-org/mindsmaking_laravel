<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('baby_names', function (Blueprint $table) {
            $table->id();
            $table->enum('gender', ['male', 'female', 'unisex'])->notNullable();
            $table->enum('type', ['firstname', 'lastname'])->notNullable();
            $table->string('origin', 100)->nullable();
            $table->string('theme', 100)->nullable();
            $table->string('culture', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('name', 255)->notNullable();
            $table->text('meaning')->nullable();
            $table->text('description')->nullable();
            $table->string('views', 500)->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baby_names');
    }
};
