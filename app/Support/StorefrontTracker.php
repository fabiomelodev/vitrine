<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Store;
use App\Models\StorefrontEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Registra eventos anônimos da vitrine para as métricas do lojista.
 *
 * - Não guarda IP, nome ou telefone: o "visitante" é um código que muda todo dia.
 * - Ignora robôs (Google, prévia de link do WhatsApp...) e a própria equipe da loja.
 * - Nunca derruba a página: se der erro ao gravar, segue sem registrar.
 */
class StorefrontTracker
{
    public const SOURCES = [
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'google' => 'Google',
        'youtube' => 'YouTube',
        'outros' => 'Outros sites',
        'direto' => 'Direto ou sem origem',
    ];

    protected const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|facebot|whatsapp|telegram|preview|headless|lighthouse|pingdom|curl|wget|python|okhttp|go-http/i';

    public static function record(Store $store, string $type, ?int $productId = null, ?string $detail = null, ?int $value = null): void
    {
        $request = request();

        if (static::shouldIgnore($store, $request)) {
            return;
        }

        try {
            StorefrontEvent::query()->create([
                'store_id' => $store->getKey(),
                'type' => $type,
                'product_id' => $productId,
                'visitor' => static::visitor($request),
                'source' => static::sessionSource($store),
                'detail' => $detail !== null ? Str::limit($detail, 117, '…') : null,
                'value' => $value,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Registra no máximo uma vez por pessoa e por dia (ex.: abrir o mesmo produto várias vezes).
     */
    public static function recordOnce(Store $store, string $type, ?int $productId = null, ?string $detail = null, ?int $value = null): void
    {
        $request = request();

        if (static::shouldIgnore($store, $request)) {
            return;
        }

        $detail = $detail !== null ? Str::limit($detail, 117, '…') : null;

        try {
            $exists = StorefrontEvent::query()
                ->where('store_id', $store->getKey())
                ->where('type', $type)
                ->where('visitor', static::visitor($request))
                ->where('created_at', '>=', now()->startOfDay())
                ->when($productId, fn ($q) => $q->where('product_id', $productId), fn ($q) => $q->whereNull('product_id'))
                ->when($detail !== null, fn ($q) => $q->where('detail', $detail), fn ($q) => $q->whereNull('detail'))
                ->exists();
        } catch (Throwable $e) {
            report($e);

            return;
        }

        if (! $exists) {
            static::record($store, $type, $productId, $detail, $value);
        }
    }

    /**
     * Uma visita por pessoa, por loja, por dia. Também descobre de onde a pessoa veio.
     */
    public static function visit(Store $store, Request $request): void
    {
        if (! $request->isMethod('GET') || static::shouldIgnore($store, $request)) {
            return;
        }

        $sourceKey = static::sessionKey($store, 'source');
        $detected = static::detectSource($request, $store);

        // Um link com origem (ex.: ?origem=instagram) sempre vale; senão mantém a primeira origem da sessão.
        if ($detected !== null && ($request->filled('origem') || $request->filled('utm_source') || ! session()->has($sourceKey))) {
            session()->put($sourceKey, $detected);
        }

        static::recordOnce($store, StorefrontEvent::VISIT);
    }

    /**
     * Guarda no pedido a origem do cliente.
     */
    public static function attachToOrder(Store $store, Order $order): void
    {
        $source = static::sessionSource($store);

        if ($source) {
            Order::query()->whereKey($order->getKey())->update(['source' => $source]);
        }
    }

    public static function sessionSource(Store $store): ?string
    {
        return session()->get(static::sessionKey($store, 'source'));
    }

    /**
     * Origem pela URL (?origem= ou ?utm_source=), pelo site anterior ou pelo app.
     * Retorna null quando a pessoa está navegando dentro da própria vitrine.
     */
    public static function detectSource(Request $request, ?Store $store = null): ?string
    {
        $param = trim((string) ($request->query('origem') ?: $request->query('utm_source')));

        if ($param !== '') {
            return static::normalizeSource($param);
        }

        $referer = (string) $request->headers->get('referer', '');
        $host = Str::lower((string) parse_url($referer, PHP_URL_HOST));

        if ($host !== '') {
            if ($host === Str::lower($request->getHost())) {
                return null; // navegação interna
            }

            return match (true) {
                Str::contains($host, 'instagram') => 'instagram',
                Str::contains($host, ['facebook', 'fb.me', 'fb.com']) => 'facebook',
                Str::contains($host, ['whatsapp', 'wa.me']) => 'whatsapp',
                Str::contains($host, 'tiktok') => 'tiktok',
                Str::contains($host, ['youtube', 'youtu.be']) => 'youtube',
                (bool) preg_match('/(^|\.)google\./', $host) => 'google',
                default => 'outros',
            };
        }

        // Navegadores dentro dos apps não mandam o site anterior, mas se identificam.
        $agent = (string) $request->userAgent();

        return match (true) {
            str_contains($agent, 'Instagram') => 'instagram',
            str_contains($agent, 'FBAN') || str_contains($agent, 'FBAV') => 'facebook',
            str_contains($agent, 'TikTok') || str_contains($agent, 'musical_ly') => 'tiktok',
            default => 'direto',
        };
    }

    public static function normalizeSource(string $value): string
    {
        $slug = Str::slug(Str::limit($value, 30, ''));

        return match ($slug) {
            'ig', 'insta', 'instagram', 'bio', 'stories', 'story' => 'instagram',
            'wa', 'zap', 'whats', 'whatsapp', 'status', 'grupo', 'grupos' => 'whatsapp',
            'fb', 'facebook' => 'facebook',
            'tt', 'tiktok' => 'tiktok',
            'google', 'youtube' => $slug,
            '' => 'direto',
            default => $slug,
        };
    }

    public static function sourceLabel(?string $source): string
    {
        $source ??= 'direto';

        return self::SOURCES[$source] ?? Str::headline($source);
    }

    /**
     * Código anônimo do visitante: muda a cada dia e não permite descobrir o IP.
     */
    public static function visitor(Request $request): string
    {
        return substr(hash_hmac('sha256', now()->toDateString().'|'.$request->ip().'|'.$request->userAgent(), (string) config('app.key')), 0, 16);
    }

    public static function shouldIgnore(Store $store, Request $request): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return true;
        }

        $agent = (string) $request->userAgent();

        if ($agent !== '' && preg_match(self::BOT_PATTERN, $agent)) {
            return true;
        }

        // A equipe da loja olhando a própria vitrine não conta como cliente.
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $key = 'storefront_team_'.$store->getKey();

        if (! $request->attributes->has($key)) {
            $request->attributes->set($key, $store->users()->whereKey($user->getKey())->exists());
        }

        return (bool) $request->attributes->get($key);
    }

    protected static function sessionKey(Store $store, string $suffix): string
    {
        return 'storefront.'.$store->getKey().'.'.$suffix;
    }
}
