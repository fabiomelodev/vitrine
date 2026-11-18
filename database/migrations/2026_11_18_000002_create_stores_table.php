<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('whatsapp', 20);
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('opening_hours')->nullable();

            $table->text('delivery_info')->nullable();
            $table->text('payment_info')->nullable();
            $table->text('exchange_policy')->nullable();

            $table->string('order_prefix', 6);
            $table->unsignedInteger('order_sequence')->default(1000);
            $table->text('order_message_template')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
