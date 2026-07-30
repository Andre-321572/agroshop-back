<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('boutiques', function (Blueprint $table) {
            $table->string('type', 255)->default('agricole')->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('boutiques', function (Blueprint $table) {
            $table->enum('type', ['quincaillerie', 'agricole'])->default('agricole')->after('telephone');
        });
    }
};
