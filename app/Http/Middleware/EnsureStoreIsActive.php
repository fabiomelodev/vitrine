<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia o painel de lojas suspensas pelo super admin.
 */
class EnsureStoreIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = Filament::getTenant();

        if ($store instanceof Store && ! $store->is_active && ! $request->user()?->is_super_admin) {
            abort(403, 'Esta loja está suspensa. Entre em contato com o suporte do Provador.');
        }

        return $next($request);
    }
}
