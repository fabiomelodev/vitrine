@php use App\Support\Format; @endphp
<x-storefront.layout title="Pedido #{{ $order->code }}">
    <div class="confirm"
         @if ($autoOpen) x-data x-init="setTimeout(() => { window.location.href = @js($whatsappUrl) }, 600)" @endif>
        <div class="confirm-main">
            <span class="check-circle" aria-hidden="true"><x-storefront.icon name="check" :size="30" /></span>
            <div class="stack" style="gap: 10px">
                <span class="eyebrow">Pedido #{{ $order->code }}</span>
                <h1 class="hero-title" style="text-align: left; font-size: clamp(36px, 5vw, 52px)">Seu pedido está <span class="serif">pronto</span></h1>
                <p class="muted" style="margin: 0; font-size: 16px">Estamos abrindo a conversa com a {{ $store->name }}. No WhatsApp, é só <strong style="color: var(--ink)">tocar em enviar</strong> para a loja receber.</p>
            </div>

            <div class="card-box" x-data="{ copied: false }">
                <strong>O WhatsApp não abriu?</strong>
                <div class="buy-row">
                    <a href="{{ $whatsappUrl }}" class="btn btn-whatsapp btn-lg" style="flex: 1 1 220px"><x-storefront.icon name="whatsapp" /> Abrir WhatsApp novamente</a>
                    <button type="button" class="btn btn-lg" style="flex: 1 1 180px"
                            @click="navigator.clipboard?.writeText(@js($order->whatsapp_message)); copied = true">
                        <x-storefront.icon name="copy" /> <span x-text="copied ? 'Mensagem copiada!' : 'Copiar mensagem'">Copiar mensagem</span>
                    </button>
                </div>
                <p class="small muted" style="margin: 0">No computador sem o app? Use o <a href="https://web.whatsapp.com" target="_blank" rel="noopener">WhatsApp Web</a> ou copie a mensagem e envie para <strong style="color: var(--ink)">{{ Format::phone($store->whatsapp) }}</strong>.</p>
                <div class="msg-box">{{ $order->whatsapp_message }}</div>
            </div>

            <section class="stack">
                <h2 style="font-size: 26px">E <span class="serif">agora?</span></h2>
                <ol class="steps">
                    <li style="background: var(--surface)"><span class="num" style="font-size: 28px">01</span><strong>A loja confirma</strong><span class="small muted">Disponibilidade das peças e valor final.</span></li>
                    <li style="background: var(--surface)"><span class="num" style="font-size: 28px">02</span><strong>Combinem a entrega</strong><span class="small muted">{{ $store->delivery_info ? \Illuminate\Support\Str::limit($store->delivery_info, 80) : 'Frete, retirada na loja ou motoboy.' }}</span></li>
                    <li style="background: var(--surface)"><span class="num" style="font-size: 28px">03</span><strong>Pagamento</strong><span class="small muted">{{ $store->payment_info ? \Illuminate\Support\Str::limit($store->payment_info, 80) : 'Direto com a loja.' }}</span></li>
                </ol>
                @if ($store->opening_hours)
                    <p class="small muted" style="margin: 0">Atendimento: {{ $store->opening_hours }}.</p>
                @endif
            </section>
        </div>

        <aside class="confirm-aside" aria-label="Resumo do pedido">
            <h2 style="font-size: 16px; font-weight: 600">Resumo do pedido</h2>
            <ul class="stack" style="list-style: none; margin: 0; padding: 0">
                @foreach ($order->items as $item)
                    @php $cover = Format::image($item->product?->coverImage($item->color), thumb: true); @endphp
                    <li style="display: flex; gap: 12px; align-items: center">
                        <span class="sugg-thumb" style="width: 52px; height: 66px">@if ($cover)<img src="{{ $cover }}" alt="">@endif</span>
                        <span style="flex: 1; display: flex; flex-direction: column; font-size: 13px">
                            <strong style="font-weight: 500; font-size: 14px">{{ $item->product_name }}</strong>
                            <span class="muted">{{ collect([$item->variantLabel(), $item->quantity.' un.'])->filter()->implode(' · ') }}</span>
                        </span>
                        <strong style="font-weight: 500; font-size: 14px">{{ Format::money($item->total) }}</strong>
                    </li>
                @endforeach
            </ul>
            <div class="total-row" style="border-top: 1px solid #EEEAE4; padding-top: 12px"><span style="font-weight: 600">Total estimado</span><strong>{{ Format::money($order->total) }}</strong></div>
            <p class="small muted" style="margin: 0; background: var(--bg); border-radius: 10px; padding: 10px 12px">Sua sacola foi esvaziada. Se mudar de ideia, a loja ajusta o pedido pela conversa.</p>
            <a href="{{ route('storefront.home', $store->slug) }}" class="btn">Continuar navegando</a>
        </aside>
    </div>
</x-storefront.layout>
