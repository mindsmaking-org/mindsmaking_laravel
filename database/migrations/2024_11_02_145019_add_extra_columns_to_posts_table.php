<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('table_of_content')->after('title');
            $table->json('images')->after('content')->nullable();
            $table->string('excerpt')->after('images');
            $table->json('key_facts')->after('excerpt');
            $table->json('faq')->after('key_facts');
            $table->json('sources')->after('faq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            //
        });
    }
};
