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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('code_reference', 20)->unique();
            $table->string('nom_client', 100);
            $table->string('prenom_client', 100);
            $table->string('telephone', 20);
            $table->string('email', 150)->nullable();
            $table->string('adresse_ligne1', 200)->nullable();
            $table->string('adresse_ligne2', 200)->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->string('pays', 100)->nullable()->default('Togo');
            $table->decimal('montant_ht', 10, 2)->default(0.00);
            $table->decimal('montant_tva', 10, 2)->default(0.00);
            $table->decimal('montant_ttc', 10, 2)->default(0.00);
            $table->decimal('frais_livraison', 10, 2)->nullable()->default(0.00);
            $table->decimal('montant_total', 10, 2)->default(0.00);
            $table->enum('type_livraison', ['livraison', 'retrait_agence'])->nullable();
            $table->text('adresse_livraison')->nullable();
            $table->date('date_livraison_souhaitee')->nullable();
            $table->text('instructions_livraison')->nullable();
            $table->enum('statut_commande', ['en_attente', 'confirmee', 'preparee', 'expediee', 'livree', 'annulee'])->default('en_attente');
            $table->enum('statut_paiement', ['en_attente', 'paye', 'echec', 'rembourse'])->default('en_attente');
            $table->text('notes_admin')->nullable();
            $table->string('commentaire', 255)->nullable();
            $table->string('ip_client', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Optimisation d'Indexation
            $table->index('code_reference');
            $table->index('telephone');
            $table->index('email');
            $table->index('statut_commande');
            $table->index('statut_paiement');
            $table->index('type_livraison');
            $table->index('created_at');
            $table->index('montant_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
