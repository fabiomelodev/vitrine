<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos anônimos da vitrine (visitas, produtos vistos, sacola, buscas...).
 * Não guarda IP nem dados pessoais: o visitante é um código que muda todo dia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->char('visitor', 16);
            $table->string('source', 30)->nullable();
            $table->string('detail', 120)->nullable();
            $table->unsignedInteger('value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['store_id', 'type', 'created_at']);
            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_events');
    }
};
