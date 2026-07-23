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
        Schema::create('article_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles_blog')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Optimisation d'Indexation & Clé Unique Composite
            $table->unique(['article_id', 'produit_id'], 'unique_article_produit');
            $table->index('article_id');
            $table->index('produit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_produits');
    }
};
