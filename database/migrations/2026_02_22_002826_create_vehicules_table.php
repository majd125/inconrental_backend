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
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();                          // id (INT, PK, AI)
            $table->string('marque');              // marque (VARCHAR)
            $table->string('modele');              // modele (VARCHAR)
            $table->string('immatriculation')->unique(); // VARCHAR, UNIQUE
            $table->year('annee');                  // annee (YEAR)
            
            // ENUM pour catégorie
            $table->enum('categorie', [
                'economique',
                'compacte', 
                'berline',
                'suv',
                'luxe',
                'sport'
            ]);
            
            $table->string('transmission');        // transmission (VARCHAR)
            $table->string('carburant');            // carburant (VARCHAR)
            
            // ENUM pour statut
            $table->enum('statut', [
                'disponible',
                'reservé'
            ])->default('disponible');
            
            $table->decimal('prix_base', 10, 2);    // prix_base (DECIMAL)
            $table->text('description')->nullable(); // description (TEXT, nullable)
            
            $table->timestamps();                   // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};