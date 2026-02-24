<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pour MySQL, on peut modifier directement
        Schema::table('users', function (Blueprint $table) {
            // Rendre les champs nullable
            $table->string('telephone')->nullable()->change();
            $table->text('adresse')->nullable()->change();
            $table->string('ville')->nullable()->change();
            $table->string('numero_permis')->nullable()->change();
            $table->date('date_naissance')->nullable()->change();
            
            // Définir des valeurs par défaut pour les booléens
            $table->boolean('is_admin')->default(false)->change();
            $table->boolean('is_staff')->default(false)->change();
            $table->boolean('is_driver')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remettre les champs comme avant (non nullable)
            $table->string('telephone')->nullable(false)->change();
            $table->text('adresse')->nullable(false)->change();
            $table->string('ville')->nullable(false)->change();
            $table->string('numero_permis')->nullable(false)->change();
            $table->date('date_naissance')->nullable(false)->change();
            
            // Enlever les valeurs par défaut
            $table->boolean('is_admin')->default(null)->change();
            $table->boolean('is_staff')->default(null)->change();
            $table->boolean('is_driver')->default(null)->change();
        });
    }
};