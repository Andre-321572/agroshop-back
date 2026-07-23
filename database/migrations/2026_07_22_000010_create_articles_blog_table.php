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
        Schema::create('articles_blog', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 200);
            $table->longText('contenu');
            $table->text('extrait')->nullable();
            $table->string('slug', 200)->unique();
            $table->enum('statut', ['brouillon', 'publie', 'archive'])->default('brouillon');
            $table->foreignId('auteur_id')->constrained('administrateurs');
            $table->string('image_principale', 500)->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('date_publication')->nullable();
            $table->integer('vues')->default(0);
            $table->timestamps();

            // Optimisation d'Indexation
            $table->index('slug');
            $table->index('statut');
            $table->index('auteur_id');
            $table->index('date_publication');
            $table->index('vues');

            // Recherche Fulltext (supporté sur MySQL / MariaDB)
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['titre', 'contenu', 'extrait'], 'idx_search_blog');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles_blog');
    }
};
