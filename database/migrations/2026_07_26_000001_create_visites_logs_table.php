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
        Schema::create('visites_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_adresse', 45)->index();
            $table->text('user_agent')->nullable();
            $table->string('page_visitee', 255)->index();
            $table->string('type_action', 50)->default('visite_page')->index();
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visites_logs');
    }
};
