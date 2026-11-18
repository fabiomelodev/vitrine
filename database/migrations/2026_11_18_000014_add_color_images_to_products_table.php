<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fotos por cor: {"Preto": ["stores/1/products/a.jpg", ...], "Off-white": [...]}
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('color_images')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('color_images');
        });
    }
};
