<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forma de pagamento da assinatura e cartão cadastrado (só bandeira e 4 últimos dígitos).
 * O número do cartão nunca é guardado: ele vai direto para o Asaas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('billing_method', 20)->nullable()->after('asaas_subscription_id');
            $table->string('card_brand', 20)->nullable()->after('billing_method');
            $table->string('card_last_digits', 4)->nullable()->after('card_brand');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['billing_method', 'card_brand', 'card_last_digits']);
        });
    }
};
