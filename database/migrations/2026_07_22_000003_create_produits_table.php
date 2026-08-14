<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom_commercial', 200);
            $table->text('description')->nullable();
            $table->text('composition')->nullable();
            $table->text('principes_actifs')->nullable();
            $table->text('mode_emploi')->nullable();
            $table->string('dosage_recommande', 500)->nullable();
            $table->text('precautions_usage')->nullable();
            $table->text('contre_indications')->nullable();
            $table->decimal('prix_unitaire', 10, 2);
            $table->string('unite_mesure', 50)->default('unité');
            $table->integer('stock_disponible')->default(0);
            $table->integer('stock_alerte')->nullable()->default(10);
            $table->decimal('poids', 8, 3)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->enum('statut', ['actif', 'inactif', 'rupture'])->default('actif');
            $table->boolean('featured')->default(false);
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('slug', 200)->unique();
            $table->timestamps();

            // Optimisations d'Indexation
            $table->index('nom_commercial');
            $table->index('prix_unitaire');
            $table->index('stock_disponible');
            $table->index('statut');
            $table->index('featured');

            // Recherche Fulltext (supporté sur MySQL / MariaDB)
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['nom_commercial', 'description', 'composition'], 'idx_search');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
