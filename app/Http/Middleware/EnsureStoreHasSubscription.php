<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Teste vencido ou assinatura sem pagamento: o lojista só acessa a página "Assinatura".
 */
class EnsureStoreHasSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = Filament::getTenant();

        if (! $store instanceof Store || $request->user()?->is_super_admin || $store->hasActiveAccess()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();

        if (str_contains($routeName, 'assinatura') || str_contains($routeName, 'tenant.profile') || str_contains($routeName, '.auth.')) {
            return $next($request);
        }

        return redirect()->to(url('painel/'.$store->slug.'/assinatura'));
    }
}
