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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->onDelete('cascade');
            $table->string('nom_maintenance');
            $table->date('date');
            $table->integer('kilometrage');
            $table->text('description')->nullable();
            $table->text('pieces_changees')->nullable();
            $table->decimal('cout_piece', 10, 2)->nullable();
            $table->decimal('cout_main_oeuvre', 10, 2)->nullable();
            $table->decimal('cout_total', 10, 2)->nullable();
            $table->string('garage')->nullable();
            $table->integer('prochaine_echeance_km')->nullable();
            $table->date('prochaine_echeance_date')->nullable();
            $table->enum('statut', ['en_cours', 'terminé'])->default('terminé');
            $table->text('remarques')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
