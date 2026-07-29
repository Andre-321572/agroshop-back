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
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->foreignId('gestionnaire_id')->constrained('gestionnaires')->onDelete('cascade');
            $table->enum('type', ['journalier', 'mensuel']);
            $table->string('fichier_pdf');
            $table->date('date_rapport');
            $table->boolean('statut_lecture')->default(false); // Non lu par défaut
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
