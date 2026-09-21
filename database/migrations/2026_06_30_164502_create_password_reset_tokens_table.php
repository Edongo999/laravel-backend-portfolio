<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();   // email de l’utilisateur
            $table->string('token');            // token de réinitialisation
            $table->timestamp('created_at')->nullable(); // date de création
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};