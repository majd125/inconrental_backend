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
            $table->string('nom')->after('name'); // instead of id, place after existing 'name'
            $table->string('prenom')->after('nom');
            $table->string('telephone')->after('email');
            $table->text('adresse')->after('telephone');
            $table->string('ville')->after('adresse');
            $table->string('numero_permis')->after('ville');
            $table->date('date_naissance')->after('numero_permis');
            $table->boolean('is_admin')->after('date_naissance');
            $table->boolean('is_staff')->after('is_admin');
            $table->boolean('is_driver')->after('is_staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nom',
                'prenom',
                'telephone',
                'adresse',
                'ville',
                'numero_permis',
                'date_naissance',
                'is_admin',
                'is_staff',
                'is_driver'
            ]);
        });
    }
};
