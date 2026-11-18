@php use App\Support\Format; @endphp
<div class="stack" style="gap: 24px">
    @if ($colors->isNotEmpty())
        <fieldset style="border: 0; margin: 0; padding: 0">
            <legend class="option-label">Cor: <span>{{ $color ?? 'escolha' }}</span></legend>
            <div style="display: flex; gap: 10px; flex-wrap: wrap">
                @foreach ($colors as $c)
                    <button type="button" class="color-dot-btn" wire:click="selectColor(@js($c['name']))"
                            aria-pressed="{{ $color === $c['name'] ? 'true' : 'false' }}"
                            aria-label="{{ $c['name'] }}{{ $c['available'] ? '' : ' (esgotada)' }}"
                            title="{{ $c['name'] }}" @style(['opacity: .45' => ! $c['available']])>
                        <span class="swatch" style="background: {{ $c['hex'] ?: '#ddd' }}"></span>
                    </button>
                @endforeach
            </div>
        </fieldset>
    @endif

    @if ($sizes->isNotEmpty())
        <fieldset style="border: 0; margin: 0; padding: 0" @class(['field-error' => $error && ! $size])>
            <legend class="option-label">Tamanho: <span>{{ $size ?? 'escolha' }}</span></legend>
            <div class="size-grid">
                @foreach ($sizes as $s)
                    <button type="button" class="size-btn" wire:click="selectSize(@js($s['name']))"
                            aria-pressed="{{ $size === $s['name'] ? 'true' : 'false' }}" @disabled(! $s['available'])>
                        {{ $s['name'] }}
                    </button>
                @endforeach
            </div>
        </fieldset>
    @endif

    @if ($error)
        <p class="error-text" role="alert" style="margin: 0"><x-storefront.icon name="info" :size="16" /> {{ $error }}</p>
    @endif

    @if ($soldOut)
        <div class="notice notice-neutral">Esta peça esgotou. A loja pode avisar você quando voltar.</div>
        <a href="{{ $notifyUrl }}"
           class="btn btn-whatsapp btn-lg" target="_blank" rel="noopener"><x-storefront.icon name="whatsapp" /> Avise-me quando voltar</a>
    @else
        <div class="buy-sticky stack">
            <div class="buy-row">
                <div class="qty">
                    <button type="button" wire:click="decrement" aria-label="Diminuir quantidade">−</button>
                    <span aria-live="polite">{{ $qty }}</span>
                    <button type="button" wire:click="increment" aria-label="Aumentar quantidade">+</button>
                </div>
                <button type="button" class="btn btn-dark btn-lg" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart">
                    <x-storefront.icon name="bag" /> Adicionar à sacola
                </button>
            </div>

            <div x-data="{ ask: false }">
                <button type="button" class="btn btn-whatsapp-outline btn-lg btn-block"
                        @click="{{ $cartCount > 0 ? 'ask = ! ask' : '$wire.buyNow()' }}">
                    <x-storefront.icon name="whatsapp" /> Pedir só esta peça pelo WhatsApp
                </button>
                @if ($cartCount > 0)
                    <div class="notice notice-neutral stack" x-show="ask" x-transition x-cloak style="margin-top: 12px" role="alertdialog" aria-label="Você já tem peças na sacola">
                        <strong>Você tem {{ $cartCount }} {{ $cartCount === 1 ? 'peça' : 'peças' }} na sacola</strong>
                        <span class="muted">Quer incluir esta e enviar tudo em um só pedido? Assim a loja atende você de uma vez.</span>
                        <div class="buy-row">
                            <button type="button" class="btn btn-dark" style="flex: 1 1 200px" wire:click="addToCart" @click="ask = false">Incluir e ver a sacola</button>
                            <button type="button" class="btn" style="flex: 1 1 200px" wire:click="buyNow" @click="ask = false">Enviar só esta peça</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <span class="small muted">Disponibilidade, frete e pagamento são confirmados pela loja no WhatsApp.</span>
    @endif
</div>
