<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestionnaires', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('gestionnaires', function (Blueprint $table) {
            $table->dropColumn('telephone');
        });
    }
};
