<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cópia local das cobranças da assinatura no Asaas (atualizada pelo webhook).
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('asaas_payment_id')->unique();
            $table->string('status', 40);
            $table->string('billing_type', 20)->nullable();
            $table->decimal('value', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('invoice_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
