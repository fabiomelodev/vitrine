@php use App\Support\Format; @endphp
<div>
    @if ($open || $inline)
        @unless ($inline)
            <div class="drawer-backdrop" wire:click="close"></div>
        @endunless

        <aside class="{{ $inline ? 'cart-page' : 'cart-drawer' }}" @unless ($inline) role="dialog" aria-modal="true" @endunless aria-labelledby="sacola-titulo"
               x-data @keydown.escape.window="$wire.close()">
            <div class="cart-head">
                <h2 id="sacola-titulo">Sua <span class="serif">sacola</span> <span class="small muted">({{ $count }} {{ $count === 1 ? 'peça' : 'peças' }})</span></h2>
                @unless ($inline)
                    <button type="button" class="icon-btn outline" wire:click="close" aria-label="Fechar sacola"><x-storefront.icon name="close" /></button>
                @endunless
            </div>

            <div class="cart-body">
                @forelse ($lines as $line)
                    @php $cover = Format::image($line->product->coverImage($line->variant?->color), thumb: true); @endphp
                    <div class="cart-line {{ $line->isAvailable() ? '' : 'unavailable' }}" wire:key="line-{{ $line->key }}">
                        <a href="{{ route('storefront.product', [$store->slug, $line->product->slug]) }}" class="cart-thumb" tabindex="-1" aria-hidden="true">
                            @if ($cover)<img src="{{ $cover }}" alt="">@endif
                        </a>
                        <div class="info">
                            <div class="row-between">
                                <strong style="font-weight: 500">{{ $line->product->name }}</strong>
                                <strong style="white-space: nowrap">{{ Format::money($line->total()) }}</strong>
                            </div>
                            @if ($line->variantLabel())
                                <span class="small muted">{{ $line->variantLabel() }}</span>
                            @endif
                            @if ($reason = $line->unavailableReason())
                                <span class="error-text" role="alert">{{ $reason }}</span>
                            @endif
                            @unless ($direct)
                                <div class="row-between" style="margin-top: auto">
                                    <div class="qty">
                                        <button type="button" wire:click="decrement('{{ $line->key }}')" aria-label="Diminuir quantidade de {{ $line->product->name }}">−</button>
                                        <span>{{ $line->qty }}</span>
                                        <button type="button" wire:click="increment('{{ $line->key }}')" aria-label="Aumentar quantidade de {{ $line->product->name }}">+</button>
                                    </div>
                                    <button type="button" class="link-btn" wire:click="remove('{{ $line->key }}')">Remover</button>
                                </div>
                            @else
                                <span class="small muted">Quantidade: {{ $line->qty }}</span>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="empty-box">
                        <strong>Sua sacola está vazia.</strong>
                        <a href="{{ route('storefront.home', $store->slug) }}">Ver a vitrine</a>
                    </div>
                @endforelse

                @if ($suggestions->isNotEmpty())
                    <section class="stack">
                        <h3 style="font-size: 22px">Combina com <span class="serif">o que você escolheu</span></h3>
                        @foreach ($suggestions as $sugg)
                            @php
                                $sCover = Format::image($sugg->coverImage(), thumb: true);
                                $options = $sugg->variants->where('is_available', true);
                            @endphp
                            <div class="sugg" wire:key="sugg-{{ $sugg->id }}">
                                <div style="display: flex; gap: 12px; align-items: center">
                                    <span class="sugg-thumb">@if ($sCover)<img src="{{ $sCover }}" alt="">@endif</span>
                                    <span style="flex: 1; display: flex; flex-direction: column">
                                        <span style="font-size: 14px; font-weight: 500">{{ $sugg->name }}</span>
                                        <span class="small muted">{{ Format::money($sugg->price) }}</span>
                                    </span>
                                    @if ($sugg->variants->isEmpty())
                                        <button type="button" class="btn" style="min-height: 40px; padding: 0 14px; font-size: 13px" wire:click="addSuggestion({{ $sugg->id }})">+ Adicionar</button>
                                    @else
                                        <button type="button" class="btn" style="min-height: 40px; padding: 0 14px; font-size: 13px" wire:click="pick({{ $sugg->id }})" aria-expanded="{{ $picking === $sugg->id ? 'true' : 'false' }}">+ Adicionar</button>
                                    @endif
                                </div>
                                @if ($picking === $sugg->id)
                                    <div style="border-top: 1px solid #EEEAE4; padding-top: 10px">
                                        <span class="small muted">Escolha a opção:</span>
                                        <div class="size-grid" style="margin-top: 8px">
                                            @foreach ($options as $opt)
                                                <button type="button" class="size-btn" wire:click="addSuggestion({{ $sugg->id }}, {{ $opt->id }})">{{ $opt->label() ?: 'Único' }}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>

            @if ($lines->isNotEmpty())
                <div class="cart-foot">
                    <div class="total-row"><span style="font-weight: 600">Total estimado</span><strong>{{ Format::money($total) }}</strong></div>
                    @if ($hasUnavailable)
                        <span class="small" style="color: var(--red)">Peças indisponíveis não entram no pedido.</span>
                    @endif
                    <button type="button" class="btn btn-whatsapp btn-lg" wire:click="startCheckout" @disabled($count === 0)>
                        <x-storefront.icon name="whatsapp" /> Enviar pedido pelo WhatsApp
                    </button>
                    <p class="small muted center" style="margin: 0">Disponibilidade, frete e pagamento são confirmados pela loja no WhatsApp. A sacola não reserva estoque.</p>
                    @unless ($inline)
                        <button type="button" class="link-btn center" wire:click="close">Continuar comprando</button>
                    @endunless
                </div>
            @endif
        </aside>
    @endif

    @if ($step === 'form' && ($open || $inline))
        <div class="modal-wrap">
            <div class="drawer-backdrop" wire:click="backToCart"></div>
            <form class="modal" role="dialog" aria-modal="true" aria-labelledby="dados-titulo" wire:submit="submit" novalidate
                  x-data="{
                      remember: true,
                      key: 'cliente:{{ $store->slug }}',
                      init() {
                          try {
                              const saved = JSON.parse(localStorage.getItem(this.key) || 'null');
                              if (saved && ! $wire.name && ! $wire.phone) { $wire.name = saved.name || ''; $wire.phone = saved.phone || ''; }
                          } catch (e) {}
                      },
                      save() {
                          try {
                              if (this.remember) localStorage.setItem(this.key, JSON.stringify({ name: $wire.name, phone: $wire.phone }));
                              else localStorage.removeItem(this.key);
                          } catch (e) {}
                      },
                  }"
                  @submit="save()">
                <div class="row-between" style="align-items: flex-start">
                    <div class="stack" style="gap: 6px">
                        <h2 id="dados-titulo" style="font-size: 28px; line-height: 1.05">Quase <span class="serif">lá!</span></h2>
                        <p class="small muted" style="margin: 0">Para a loja identificar seu pedido ({{ $count }} {{ $count === 1 ? 'peça' : 'peças' }} · {{ Format::money($total) }}).</p>
                    </div>
                    <button type="button" class="icon-btn outline" wire:click="backToCart" aria-label="Fechar"><x-storefront.icon name="close" :size="16" /></button>
                </div>

                <label class="field">Seu nome
                    <input type="text" autocomplete="name" wire:model="name" placeholder="Ex.: Mariana Souza"
                           aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="erro-nome">
                    @error('name')<span id="erro-nome" class="error-text" role="alert">{{ $message }}</span>@enderror
                </label>

                <label class="field">WhatsApp
                    <input type="tel" inputmode="numeric" autocomplete="tel-national" wire:model.blur="phone" placeholder="(11) 91234-5678"
                           aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="erro-tel">
                    @error('phone')<span id="erro-tel" class="error-text" role="alert">{{ $message }}</span>@enderror
                </label>

                <label class="check-row"><input type="checkbox" x-model="remember"> Lembrar meus dados neste aparelho</label>

                <button type="submit" class="btn btn-whatsapp btn-lg" wire:loading.attr="disabled" wire:target="submit">
                    <x-storefront.icon name="whatsapp" />
                    <span wire:loading.remove wire:target="submit">Continuar no WhatsApp</span>
                    <span wire:loading wire:target="submit">Enviando…</span>
                </button>
                <p class="small muted center" style="margin: 0">Seus dados vão só para a {{ $store->name }}, para atender este pedido.</p>
            </form>
        </div>
    @endif
</div>
