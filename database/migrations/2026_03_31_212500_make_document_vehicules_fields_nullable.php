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
        Schema::table('document_vehicules', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
            $table->string('numero')->nullable()->change();
            $table->date('date_debut')->nullable()->change();
            $table->date('date_expiration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_vehicules', function (Blueprint $table) {
            // Depending on database engine, reversing to non-nullable enum might require raw queries or dropping the column.
            // Leaving simple string change as fallback.
            $table->string('type')->nullable(false)->change();
            $table->string('numero')->nullable(false)->change();
            $table->date('date_debut')->nullable(false)->change();
            $table->date('date_expiration')->nullable(false)->change();
        });
    }
};
