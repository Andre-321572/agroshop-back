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
        Schema::create('produit_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->foreignId('categorie_id')->constrained('categories')->cascadeOnDelete();
            $table->boolean('principale')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Optimisations & Clé Unique Composite
            $table->unique(['produit_id', 'categorie_id'], 'unique_produit_categorie');
            $table->index('produit_id');
            $table->index('categorie_id');
            $table->index('principale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_categories');
    }
};
