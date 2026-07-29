<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Créer la table pivot many-to-many
        Schema::create('boutique_gestionnaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->foreignId('gestionnaire_id')->constrained('gestionnaires')->onDelete('cascade');
            $table->unique(['boutique_id', 'gestionnaire_id']); // Pas de doublon
            $table->timestamps();
        });

        // 2. Migrer les associations existantes vers la pivot table
        //    (ancienne colonne boutique_id dans gestionnaires → nouvelle pivot)
        if (Schema::hasColumn('gestionnaires', 'boutique_id')) {
            $gestionnaires = DB::table('gestionnaires')->get();
            foreach ($gestionnaires as $g) {
                if ($g->boutique_id) {
                    DB::table('boutique_gestionnaire')->insertOrIgnore([
                        'boutique_id'     => $g->boutique_id,
                        'gestionnaire_id' => $g->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            // 3. Supprimer l'ancienne colonne
            Schema::table('gestionnaires', function (Blueprint $table) {
                $table->dropForeign(['boutique_id']);
                $table->dropColumn('boutique_id');
            });
        }
    }

    public function down(): void
    {
        // Recréer la colonne boutique_id (nullable pour rollback)
        Schema::table('gestionnaires', function (Blueprint $table) {
            $table->foreignId('boutique_id')->nullable()->constrained('boutiques')->onDelete('set null');
        });

        Schema::dropIfExists('boutique_gestionnaire');
    }
};
