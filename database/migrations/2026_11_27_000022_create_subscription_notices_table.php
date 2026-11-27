<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos de assinatura já enviados por e-mail (evita mandar o mesmo aviso duas vezes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('reference', 60);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['store_id', 'type', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_notices');
    }
};
