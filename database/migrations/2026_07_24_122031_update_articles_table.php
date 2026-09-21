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
    Schema::table('articles', function (Blueprint $table) {
        // ⚡ Ajout du champ category
        $table->string('category')->default('Tech');

        // ⚡ Suppression du champ views
        $table->dropColumn('views');
    });
}

public function down(): void
{
    Schema::table('articles', function (Blueprint $table) {
        $table->integer('views')->default(0);
        $table->dropColumn('category');
    });
}

};