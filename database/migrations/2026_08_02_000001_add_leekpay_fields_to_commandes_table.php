<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('leekpay_checkout_id')->nullable()->after('statut_paiement');
            $table->string('leekpay_transaction_id')->nullable()->after('leekpay_checkout_id');
            $table->text('leekpay_payment_url')->nullable()->after('leekpay_transaction_id');
            $table->string('mode_paiement')->nullable()->default('especes')->after('leekpay_payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn([
                'leekpay_checkout_id',
                'leekpay_transaction_id',
                'leekpay_payment_url',
                'mode_paiement',
            ]);
        });
    }
};
