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
        Schema::create('produit_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->string('nom_document', 200);
            $table->enum('type_document', ['fiche_technique', 'guide_utilisation', 'certificat', 'autre']);
            $table->string('url_document', 500);
            $table->integer('taille_fichier')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Optimisations d'Indexation
            $table->index('produit_id');
            $table->index('type_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_documents');
    }
};
