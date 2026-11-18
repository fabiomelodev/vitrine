<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->string('image_mobile_path')->nullable();

            // Para onde o banner leva: nada, uma categoria, um produto ou um endereço.
            $table->string('link_type', 20)->default('none');
            $table->foreignId('link_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('link_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('link_url', 500)->nullable();
            $table->boolean('open_in_new_tab')->default(false);

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
