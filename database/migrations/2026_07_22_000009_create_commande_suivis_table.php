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
        Schema::create('commande_suivis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('statut_precedent', 50)->nullable();
            $table->string('nouveau_statut', 50);
            $table->text('commentaire')->nullable();
            $table->foreignId('utilisateur_id')->nullable()->constrained('administrateurs')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Optimisation d'Indexation
            $table->index('commande_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_suivis');
    }
};
