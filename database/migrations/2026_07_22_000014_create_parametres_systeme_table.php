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
        Schema::create('parametres_systeme', function (Blueprint $table) {
            $table->id();
            $table->string('cle_parametre', 100)->unique();
            $table->text('valeur_parametre')->nullable();
            $table->text('description_parametre')->nullable();
            $table->enum('type_parametre', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Optimisation d'Indexation
            $table->index('cle_parametre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_systeme');
    }
};
