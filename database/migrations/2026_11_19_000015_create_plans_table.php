<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('max_products')->nullable();
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Plano único inicial (preço e limites editáveis no /admin).
        DB::table('plans')->insert([
            'name' => 'Provador',
            'description' => 'Tudo incluído: vitrine, sacola, pedidos pelo WhatsApp e painel.',
            'price' => 49.90,
            'max_products' => 200,
            'trial_days' => 14,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
