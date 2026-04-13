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
        Schema::create('transfer_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade');
            $table->string('lieu_depart');
            $table->string('lieu_destination');
            $table->datetime('date_heure_depart');
            $table->string('type_trajet'); // simple, same_day, diff_days
            $table->string('duree_attente')->nullable(); // half, full
            $table->datetime('date_heure_retour')->nullable();
            $table->integer('nb_adultes');
            $table->integer('nb_enfants')->default(0);
            $table->integer('nb_bebes')->default(0);
            $table->decimal('montant_total', 10, 2)->nullable(); // Set by admin
            $table->string('statut')->default('en_attente_prix'); // en_attente_prix, en_attente_confirmation, confirme, annule
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_reservations');
    }
};
