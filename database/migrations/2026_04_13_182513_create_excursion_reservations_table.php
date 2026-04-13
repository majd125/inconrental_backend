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
        Schema::create('excursion_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('excursion_id')->constrained('excursions')->onDelete('cascade');
            $table->date('date_reservation');
            $table->string('lieu_depart');
            $table->integer('nb_adultes')->default(1);
            $table->integer('nb_enfants')->default(0);
            $table->integer('nb_bebes')->default(0);
            $table->decimal('montant_total', 10, 2);
            $table->enum('statut', ['en_attente', 'confirme', 'annule', 'termine'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excursion_reservations');
    }
};
