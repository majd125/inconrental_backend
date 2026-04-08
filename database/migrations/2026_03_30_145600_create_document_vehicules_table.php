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
        Schema::create('document_vehicules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->onDelete('cascade');
            $table->enum('type', ['carte_grise', 'assurance', 'vignette', 'visite_technique']);
            $table->string('numero');
            $table->date('date_debut');
            $table->date('date_expiration');
            $table->string('organisme')->nullable();
            $table->decimal('montant', 10, 2)->nullable();
            $table->enum('statut', ['validé', 'expiré'])->default('validé');
            $table->text('Remarques')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_vehicules');
    }
};
