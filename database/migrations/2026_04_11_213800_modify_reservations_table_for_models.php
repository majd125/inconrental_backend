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
        Schema::table('reservations', function (Blueprint $table) {
            // Drop foreign key before altering the column
            $table->dropForeign(['vehicule_id']);
            
            // Make vehicule_id nullable
            $table->foreignId('vehicule_id')->nullable()->change();
            
            // Add foreign key constraint back
            $table->foreign('vehicule_id')->references('id')->on('vehicules')->onDelete('cascade');
            
            // Add modele column to store the reference model name
            $table->string('modele')->nullable()->after('vehicule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('modele');
            
            $table->dropForeign(['vehicule_id']);
            $table->foreignId('vehicule_id')->nullable(false)->change();
            $table->foreign('vehicule_id')->references('id')->on('vehicules')->onDelete('cascade');
        });
    }
};
