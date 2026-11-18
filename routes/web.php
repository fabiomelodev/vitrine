<?php

use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use App\Models\Store;
use Illuminate\Support\Facades\Route;

// Site do Provador
Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::get('/termos', [SiteController::class, 'terms'])->name('site.terms');
Route::get('/privacidade', [SiteController::class, 'privacy'])->name('site.privacy');

// Avisos de pagamento do Asaas (sem proteção CSRF; autenticado pelo token do webhook).
Route::post('/webhooks/asaas', AsaasWebhookController::class)
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('webhooks.asaas');

/*
|--------------------------------------------------------------------------
| Vitrine pública de cada loja: /{slug-da-loja}/...
|--------------------------------------------------------------------------
| O padrão abaixo impede que endereços do sistema (admin, painel, livewire...)
| sejam tratados como lojas.
*/
$reserved = implode('|', array_map('preg_quote', Store::RESERVED_SLUGS));

Route::prefix('{storeSlug}')
    ->where(['storeSlug' => '(?!(?:'.$reserved.')(?![a-z0-9-]))(?!livewire)[a-z0-9][a-z0-9-]*'])
    ->controller(StorefrontController::class)
    ->name('storefront.')
    ->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/produtos', 'products')->name('products');
        Route::get('/c/{categorySlug}', 'category')->name('category');
        Route::get('/p/{productSlug}', 'product')->name('product');
        Route::get('/busca', 'search')->name('search');
        Route::get('/sacola', 'cart')->name('cart');
        Route::get('/favoritos', 'favorites')->name('favorites');
        Route::get('/sobre', 'about')->name('about');
        Route::get('/pedido/{token}', 'order')->name('order');
    });
