@php use App\Support\Format; $logo = Format::image($store->logo_path); @endphp
<x-storefront.layout title="Sobre a loja">
    <section class="section container" style="display: flex; flex-wrap: wrap; gap: 48px; align-items: center">
        <div class="stack" style="flex: 1 1 420px; gap: 18px">
            <h1 class="hero-title" style="text-align: left; font-size: clamp(40px, 5vw, 64px)">Sobre a <span class="serif">{{ $store->name }}</span></h1>
            @if ($store->description)
                <p class="muted" style="font-size: 17px; margin: 0; white-space: pre-line">{{ $store->description }}</p>
            @endif
            <div style="display: flex; gap: 10px; flex-wrap: wrap">
                <a href="{{ $store->whatsappUrl() }}" class="btn btn-whatsapp" target="_blank" rel="noopener"><x-storefront.icon name="whatsapp" /> Falar com a loja</a>
                @if ($store->instagram)
                    <a href="https://instagram.com/{{ ltrim($store->instagram, '@') }}" class="btn" target="_blank" rel="noopener">{{ '@'.ltrim($store->instagram, '@') }}</a>
                @endif
            </div>
        </div>
        @if ($logo)
            <div style="flex: 1 1 320px; display: flex; justify-content: center"><img src="{{ $logo }}" alt="{{ $store->name }}" style="max-height: 240px"></div>
        @endif
    </section>

    <section class="section section-alt">
        <div class="container info-cards">
            @if ($store->address || $store->city)
                <div class="info-card"><span class="eyebrow">Endereço</span><strong style="font-weight: 500">{{ collect([$store->address, $store->city ? $store->city.($store->state ? ' — '.$store->state : '') : null])->filter()->implode(', ') }}</strong>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(collect([$store->address, $store->city, $store->state])->filter()->implode(', ')) }}" target="_blank" rel="noopener" class="small">Ver no mapa</a></div>
            @endif
            @if ($store->opening_hours)
                <div class="info-card"><span class="eyebrow">Horário</span><strong style="font-weight: 500">{{ $store->opening_hours }}</strong></div>
            @endif
            <div class="info-card"><span class="eyebrow">WhatsApp</span><strong style="font-weight: 500">{{ Format::phone($store->whatsapp) }}</strong><span class="small muted">Pedidos e dúvidas.</span></div>
            @if ($store->delivery_info)
                <div class="info-card"><span class="eyebrow">Entrega</span><span class="policy" style="color: var(--ink)">{{ $store->delivery_info }}</span></div>
            @endif
            @if ($store->payment_info)
                <div class="info-card"><span class="eyebrow">Pagamento</span><span class="policy" style="color: var(--ink)">{{ $store->payment_info }}</span></div>
            @endif
        </div>
    </section>

    <section class="section container" id="trocas" style="display: flex; flex-wrap: wrap; gap: 48px">
        <div style="flex: 1 1 300px">
            <h2 class="section-title">Trocas e <span class="serif">devoluções</span></h2>
            <p class="muted">Em caso de dúvida, fale com a loja antes de enviar a peça.</p>
        </div>
        <div style="flex: 2 1 520px" class="stack">
            @if ($store->exchange_policy)
                <p class="policy" style="margin: 0">{{ $store->exchange_policy }}</p>
            @else
                <p class="muted" style="margin: 0">A loja ainda não publicou a política de trocas. Pergunte pelo WhatsApp.</p>
            @endif
            <div class="notice notice-neutral">Compras feitas fora da loja física podem ser canceladas em até 7 dias após o recebimento, conforme o Código de Defesa do Consumidor.</div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container" style="max-width: 880px">
            <h2 class="section-title" style="margin-bottom: 20px">Dúvidas <span class="serif">frequentes</span></h2>
            <div class="details">
                <details><summary>Como funciona o pedido pelo WhatsApp?</summary><p>Você monta a sacola e envia. A mensagem chega pronta para a loja, que confirma disponibilidade, frete e pagamento com você.</p></details>
                <details><summary>Preciso criar uma conta?</summary><p>Não. Sua sacola e seus favoritos ficam salvos neste navegador.</p></details>
                <details><summary>A peça fica reservada quando eu envio o pedido?</summary><p>A reserva acontece quando a loja confirma o pedido no WhatsApp.</p></details>
            </div>
        </div>
    </section>
</x-storefront.layout>
