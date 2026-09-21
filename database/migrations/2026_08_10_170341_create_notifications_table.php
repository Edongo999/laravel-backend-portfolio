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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');                // ex: user, article, password
            $table->string('message');             // texte de la notification
            $table->unsignedBigInteger('user_id'); // utilisateur concerné
            $table->boolean('read')->default(false); // lu/non-lu
            $table->timestamps();

            // ✅ Clé étrangère vers users
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};