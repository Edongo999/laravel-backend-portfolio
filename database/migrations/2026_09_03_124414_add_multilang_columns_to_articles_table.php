<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_fr')->nullable();
            $table->text('content_fr')->nullable();
            $table->string('title_en')->nullable();
            $table->text('content_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['title_fr', 'content_fr', 'title_en', 'content_en']);
        });
    }
};