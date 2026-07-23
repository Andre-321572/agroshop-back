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
        Schema::create('produit_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->string('nom_fichier', 255);
            $table->string('url_image', 500);
            $table->string('alt_text', 200)->nullable();
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('principale')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Optimisation d'Indexation
            $table->index('produit_id');
            $table->index('ordre_affichage');
            $table->index('principale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_images');
    }
};
