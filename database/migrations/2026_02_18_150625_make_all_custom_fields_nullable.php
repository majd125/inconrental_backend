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
        Schema::table('users', function (Blueprint $table) {
            // Rendre tous tes champs personnalisés NULLABLE
            $table->string('telephone')->nullable()->change();
            $table->text('adresse')->nullable()->change();
            $table->string('ville')->nullable()->change();
            $table->string('numero_permis')->nullable()->change();
            $table->date('date_naissance')->nullable()->change();
            
            // Pour les booléens, on leur donne une valeur par défaut
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
            // Revenir en arrière (NOT NULL)
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