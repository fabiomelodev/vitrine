@php
    use App\Support\Format;

    // Fotos do site: basta colocar o arquivo em public/images/site/ (jpg ou webp).
    $photo = function (string $name): ?string {
        foreach (['jpg', 'jpeg', 'webp', 'png'] as $ext) {
            if (is_file(public_path("images/site/{$name}.{$ext}"))) {
                return asset("images/site/{$name}.{$ext}");
            }
        }

        return null;
    };
    $photoCounter = $photo('lojista-balcao');
    $photoPhone = $photo('lojista-celular');

    $host = request()->getHost();
    $trialDays = $plan?->trial_days;
    $perDay = $plan?->price ? ceil(((float) $plan->price / 30) * 10) / 10 : null;

    $check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>';
    $wa = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 19l-4 1z"/></svg>';
    $arrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $imgIcon = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-8 8"/></svg>';
@endphp
<x-site.layout>
    {{-- Topo --}}
    <section class="s-hero">
        <div class="s-wrap s-row">
            <div class="s-col s-text" style="gap: 22px">
                <h1>Sua loja com <span class="serif">vitrine online</span> e pedidos prontos no WhatsApp</h1>
                <p class="lead">O cliente escolhe a peça, a cor e o tamanho sozinho. Você recebe uma mensagem com tudo anotado e só combina a entrega e o pagamento.</p>
                <div class="s-actions">
                    <a href="{{ url('painel/register') }}" class="btn btn-dark btn-lg">{{ $trialDays ? 'Criar minha loja grátis' : 'Criar minha loja' }} {!! $arrow !!}</a>
                    <a href="#funcionando" class="btn btn-lg s-btn-light"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/></svg> Ver funcionando</a>
                </div>
                <ul class="s-checks">
                    @if ($trialDays)
                        <li>{!! $check !!} {{ $trialDays }} dias grátis, sem cartão</li>
                    @endif
                    <li>{!! $check !!} Sem taxa por venda</li>
                    <li>{!! $check !!} Sem fidelidade</li>
                </ul>
            </div>

            <div class="s-col">
                <div class="s-phone-stage">
                    <div class="s-phone" role="img" aria-label="Celular mostrando a vitrine de uma loja de roupas">
                        <div class="s-screen">
                            <div class="s-notch"><span></span></div>
                            <div class="m-bar"><span class="m-dot"></span><span class="brand-mini"><span class="serif" style="color: var(--ink)">atelier</span><b>LUME</b></span><span class="m-bag">2</span></div>
                            <div class="m-banner"><span>Nova coleção</span><em>Verão leve</em></div>
                            <div class="m-chips"><span class="on">Todos</span><span>Vestidos</span><span>Blusas</span></div>
                            <div class="m-grid">
                                <div><i style="background: #E7E2DB"></i>Vestido midi de linho<b>R$ 289,90</b></div>
                                <div><i style="background: #D9D2C7"></i>Calça pantalona<b>R$ 229,90</b></div>
                                <div><i style="background: #E4DED5"></i>Bolsa tote couro cru<b>R$ 329,90</b></div>
                                <div><i style="background: #EAE6E1"></i>Blazer alongado<b>R$ 389,90</b></div>
                            </div>
                        </div>
                    </div>
                    <div class="s-float" style="left: 0; top: 300px">
                        <span class="s-float-icon">{!! $wa !!}</span>
                        <span><strong>Novo pedido #AL-1024</strong><small>2 peças · R$ 519,80</small></span>
                    </div>
                    <div class="s-float" style="right: 0; bottom: 110px">
                        <span style="display: flex; gap: 4px; flex: 0 0 auto"><i style="width: 16px; height: 16px; border-radius: 999px; background: #3B5B7A"></i><i style="width: 16px; height: 16px; border-radius: 999px; background: #D8CBB4; border: 1px solid #BDB5AA"></i></span>
                        <span><strong>Azul · M</strong><small>escolhido pela cliente</small></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Faixa de vantagens --}}
    <section class="s-strip" aria-label="Vantagens">
        <div class="s-wrap">
            <div class="s-strip-item"><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><span><strong>Pronta em minutos</strong><span>sem precisar de site ou programador</span></span></div>
            <div class="s-strip-item"><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/></svg></span><span><strong>Sem taxa por venda</strong><span>o valor da venda é todo seu</span></span></div>
            <div class="s-strip-item"><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/></svg></span><span><strong>Feita para o celular</strong><span>onde seu cliente já está</span></span></div>
            <div class="s-strip-item"><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.3M20 12a8 8 0 0 1-14 5.3"/><path d="M18 3v4h-4M6 21v-4h4"/></svg></span><span><strong>Cancele quando quiser</strong><span>sem fidelidade nem multa</span></span></div>
        </div>
    </section>

    {{-- Problema: foto por foto --}}
    <section class="s-section" id="vantagens">
        <div class="s-wrap">
            <div class="s-row">
                <figure class="s-photo s-col">
                    @if ($photoCounter)
                        <img src="{{ $photoCounter }}" alt="Lojista atendendo uma cliente no balcão da loja" loading="lazy">
                    @else
                        <span class="s-photo-empty">{!! $imgIcon !!} Foto: lojista atendendo cliente no balcão</span>
                    @endif
                    <span class="s-float">
                        <span class="s-float-icon red">{!! $wa !!}</span>
                        <span><strong>14 mensagens não lidas</strong><small>“tem no M?” “quanto fica?”</small></span>
                    </span>
                </figure>
                <div class="s-col s-text">
                    <span class="eyebrow">Por que usar</span>
                    <h2 class="s-title">Pare de mandar <span class="serif">foto por foto</span></h2>
                    <p>Enquanto você atende no balcão, o celular não para: “tem no M?”, “manda da azul”, “quanto fica?”. Cada venda vira uma conversa longa, e quem espera desiste.</p>
                    <p>Com o Provador, o cliente vê fotos, cores, tamanhos e preços sozinho e manda o pedido pronto.</p>
                </div>
            </div>

            <div class="s-compare">
                <div class="compare-col">
                    <div class="compare-label bad">Hoje, sem catálogo</div>
                    <div class="chat" aria-label="Exemplo de conversa sem catálogo">
                        <div class="bubble in">Oi! Tem aquele vestido do story?</div>
                        <div class="bubble out">Tenho sim! Vou mandar as fotos</div>
                        <div class="bubble out photos" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                        <div class="bubble in">Tem no M? E na cor azul?</div>
                        <div class="bubble in">Quanto fica?</div>
                        <div class="bubble in">Manda a calça também</div>
                        <div class="chat-pending">Respondendo depois de atender a loja…</div>
                    </div>
                    <ul class="compare-list bad">
                        <li>Dezenas de mensagens até fechar uma venda</li>
                        <li>Cliente esperando enquanto você atende no balcão</li>
                        <li>Erro de tamanho, cor e preço na hora de anotar</li>
                    </ul>
                </div>
                <div class="compare-col">
                    <div class="compare-label good">Com o Provador</div>
                    <div class="chat" aria-label="Exemplo de pedido recebido pelo Provador">
                        <div class="bubble in order">Olá, Atelier Lume! Sou Marina e quero fazer o pedido <strong>#AL-1024</strong>:<br>
                            1x Vestido midi de linho — Azul / M — R$ 289,90<br>
                            1x Calça pantalona — Areia / 38 — R$ 229,90<br>
                            <strong>Total estimado: R$ 519,80</strong></div>
                        <div class="bubble out">Perfeito, Marina! Separando suas peças.</div>
                        <div class="chat-pending ok">Pedido #AL-1024 salvo no seu painel</div>
                    </div>
                    <ul class="compare-list good">
                        <li>Uma mensagem com peças, cores, tamanhos e total</li>
                        <li>O cliente escolhe sozinho, a qualquer hora</li>
                        <li>Pedido com código e salvo no painel para conferir</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Veja funcionando --}}
    <section class="s-section s-dark" id="funcionando">
        <div class="s-wrap">
            <div class="s-head">
                <span class="eyebrow">Veja funcionando</span>
                <h2 class="s-title">Em poucos minutos, <span class="serif">sua loja no ar</span></h2>
                <p>Quatro passos do cadastro ao primeiro pedido. Toque em cada passo para ver a tela.</p>
            </div>
            <div class="s-row" style="margin-top: 40px" data-demo>
                <div class="s-col s-steps">
                    @foreach ([
                        ['Cadastre sua loja', 'Nome, logo e o WhatsApp que vai receber os pedidos.'],
                        ['Escolha seu link', 'Um endereço só seu para colocar na bio, no status e nos grupos.'],
                        ['Cadastre as peças', 'Fotos por cor, preço e tamanhos. Marque o que esgotou.'],
                        ['Receba o pedido pronto', 'O cliente monta a sacola e a mensagem chega com tudo anotado.'],
                    ] as $i => [$title, $text])
                        <button type="button" class="s-step" data-step="{{ $i }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                            <span class="s-step-num">{{ sprintf('%02d', $i + 1) }}</span>
                            <span><strong>{{ $title }}</strong><span>{{ $text }}</span></span>
                        </button>
                    @endforeach
                    <a href="{{ url('painel/register') }}" class="btn btn-lg s-btn-cream s-demo-cta">Criar minha loja agora {!! $arrow !!}</a>
                </div>

                <div class="s-col">
                    <div class="s-phone" style="margin-inline: auto" role="img" aria-label="Demonstração das telas do Provador">
                        <div class="s-screen">
                            <div class="s-notch"><span></span></div>

                            <div class="s-pane" data-pane="0">
                                <span class="s-pane-tag">Painel · Minha loja</span>
                                <strong class="s-pane-title">Dados da loja</strong>
                                <div class="s-logo-box"><span class="s-avatar">al</span><span><strong style="font-size: 13px; display: block">Logo da loja</strong><span class="muted">Toque para enviar</span></span></div>
                                <div class="s-field">Nome da loja<span class="focus">Atelier Lume</span></div>
                                <div class="s-field">WhatsApp que recebe os pedidos<span>(11) 90000-0000</span></div>
                                <div class="s-field">Instagram<span>@atelierlume</span></div>
                                <span class="s-fake-btn">Salvar</span>
                            </div>

                            <div class="s-pane" data-pane="1" hidden>
                                <span class="s-pane-tag">Painel · Endereço da vitrine</span>
                                <strong class="s-pane-title">Escolha seu link</strong>
                                <div class="s-field"><span class="ok"><small class="muted" style="font-size: 14px">{{ $host }}/</small><b>atelierlume</b></span></div>
                                <span class="s-ok-text">{!! $check !!} Endereço disponível</span>
                                <div class="s-mini-card">
                                    <span class="muted">Assim fica na bio do Instagram:</span>
                                    <div style="display: flex; gap: 10px; align-items: center"><span class="s-avatar">al</span><span><strong style="font-size: 13px; display: block">@atelierlume</strong><span style="color: #3B5B7A">{{ $host }}/atelierlume</span></span></div>
                                </div>
                                <div class="s-tiles"><span>Bio</span><span>Status</span><span>Grupos</span></div>
                            </div>

                            <div class="s-pane" data-pane="2" hidden>
                                <span class="s-pane-tag">Painel · Novo produto</span>
                                <div class="s-tiles"><i style="background: #D9D2C7"></i><i style="background: #3B5B7A"></i><i class="add">+</i></div>
                                <div class="s-field">Nome<span>Vestido midi de linho</span></div>
                                <div class="s-field">Preço<span>R$ 289,90</span></div>
                                <strong>Cores</strong>
                                <div class="s-color-chips"><span class="on"><i style="background: #D8CBB4; border: 1px solid #BDB5AA"></i>Areia</span><span><i style="background: #3B5B7A"></i>Azul</span><span><i style="background: #1C1A17"></i>Preto</span></div>
                                <strong>Tamanhos</strong>
                                <div class="s-sizes"><span class="on">P</span><span class="on">M</span><span class="on">G</span><span class="off">GG</span></div>
                                <span class="muted" style="font-size: 11px">GG marcado como esgotado</span>
                            </div>

                            <div class="s-pane wa" data-pane="3" hidden>
                                <div class="s-wa-head"><b>M</b><span><strong>Marina</strong><small>cliente</small></span></div>
                                <div class="chat">
                                    <div class="bubble in order">Olá, Atelier Lume! Sou Marina e quero fazer o pedido <strong>#AL-1024</strong>:<br>1x Vestido midi de linho — Azul / M — R$ 289,90<br>1x Calça pantalona — Areia / 38 — R$ 229,90<br><strong>Total estimado: R$ 519,80</strong></div>
                                    <div class="bubble out">Oi, Marina! Tenho tudo. Te mando o Pix e o frete.</div>
                                    <div class="chat-pending ok">Pedido #AL-1024 salvo no painel</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Recursos --}}
    <section class="s-section" id="recursos">
        <div class="s-wrap">
            <div class="s-head">
                <span class="eyebrow">Recursos</span>
                <h2 class="s-title">Tudo que sua loja <span class="serif">precisa</span></h2>
            </div>

            <div class="s-features">
                <div class="s-row s-feature">
                    <div class="s-col s-text">
                        <h3>Fotos por cor e grade de tamanhos</h3>
                        <p>O cliente toca na cor e vê as fotos dela. Tamanho que acabou aparece riscado e não entra no pedido.</p>
                    </div>
                    <div class="s-col s-showcase" aria-hidden="true">
                        <div class="s-card-ui s-product-ui">
                            <i></i>
                            <div>
                                <strong style="font-size: 16px">Vestido midi de linho</strong>
                                <strong>R$ 289,90</strong>
                                <span class="small"><strong>Cor:</strong> <span class="muted">Azul</span></span>
                                <div class="s-ring-row"><span class="s-ring"><i style="background: #D8CBB4"></i></span><span class="s-ring on"><i style="background: #3B5B7A"></i></span><span class="s-ring"><i style="background: #1C1A17"></i></span></div>
                                <div class="s-sizes"><span>P</span><span class="on">M</span><span>G</span><span class="off">GG</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="s-row reverse s-feature">
                    <div class="s-col s-showcase" aria-hidden="true">
                        <div class="s-card-ui s-bag-ui">
                            <header><strong>Sua sacola</strong><span class="small muted">2 peças</span></header>
                            <div class="s-bag-body">
                                <div class="s-bag-line"><i style="background: #3B5B7A"></i><span><b>Vestido midi de linho</b><span class="muted">Azul · M</span><b>R$ 289,90</b></span></div>
                                <div class="s-bag-line"><i style="background: #D8CBB4"></i><span><b>Calça pantalona</b><span class="muted">Areia · 38</span><b>R$ 229,90</b></span></div>
                                <div class="s-sugg"><i></i><span><span class="muted">Complete o look</span><b>Bolsa tote couro cru</b></span><em>+ Adicionar</em></div>
                            </div>
                            <div class="s-bag-foot">
                                <div class="row-between"><span class="small muted">Total estimado</span><strong style="font-size: 18px">R$ 519,80</strong></div>
                                <span class="s-wa-btn">{!! $wa !!} Enviar pedido pelo WhatsApp</span>
                            </div>
                        </div>
                    </div>
                    <div class="s-col s-text">
                        <h3>Sacola e “Complete o look”</h3>
                        <p>O cliente junta várias peças num pedido só. Você escolhe as sugestões que aparecem junto, como uma vendedora oferecendo o acessório certo.</p>
                    </div>
                </div>

                <div class="s-row s-feature">
                    <div class="s-col s-text">
                        <h3>Pedidos organizados no painel</h3>
                        <p>Cada pedido chega com código, itens e total. Você recebe aviso no painel e por e-mail e acompanha o andamento: novo, em atendimento, vendido.</p>
                    </div>
                    <div class="s-col s-showcase" aria-hidden="true">
                        <div class="s-card-ui s-orders-ui">
                            <header><strong>Pedidos</strong><span class="s-bell"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg><b>1</b></span></header>
                            <div class="s-order-row"><span><strong>#AL-1024 · Marina</strong><small>2 peças · R$ 519,80</small></span><span class="s-badge new">Novo</span></div>
                            <div class="s-order-row"><span><strong>#AL-1023 · Juliana</strong><small>1 peça · R$ 389,90</small></span><span class="s-badge conf">Em atendimento</span></div>
                            <div class="s-order-row"><span><strong>#AL-1022 · Carla</strong><small>3 peças · R$ 712,70</small></span><span class="s-badge sent">Vendido</span></div>
                        </div>
                    </div>
                </div>

                <div class="s-small-cards">
                    <div><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8"/></svg></span><strong>Vitrine com a sua cara</strong><span class="muted">Logo, banners em carrossel, categorias e página “Sobre a loja” com trocas e horários.</span></div>
                    <div><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg></span><strong>Favoritos e busca</strong><span class="muted">O cliente guarda as peças que gostou e encontra o que procura em segundos.</span></div>
                    <div><span class="s-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h12"/></svg></span><strong>Pagamento do seu jeito</strong><span class="muted">Pix, cartão ou dinheiro: você recebe direto do cliente, como já faz hoje.</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Vende com a loja fechada --}}
    <section class="s-section s-alt" aria-label="Venda com a loja fechada">
        <div class="s-wrap s-row reverse">
            <div class="s-col s-text">
                <h2 class="s-title">Sua vitrine vende <span class="serif">até com a loja fechada</span></h2>
                <p>De madrugada, no domingo ou enquanto você atende outra cliente: o pedido fica esperando no WhatsApp e no painel, com tudo anotado.</p>
                <ul class="s-list">
                    <li>{!! $check !!} Responda quando puder, sem perder a venda</li>
                    <li>{!! $check !!} Um link para bio, status e grupos</li>
                    <li>{!! $check !!} Aviso de pedido novo por e-mail</li>
                </ul>
            </div>
            <figure class="s-photo warm s-col">
                @if ($photoPhone)
                    <img src="{{ $photoPhone }}" alt="Lojista sorrindo enquanto olha o celular" loading="lazy">
                @else
                    <span class="s-photo-empty" style="color: #3E3226">{!! $imgIcon !!} Foto: lojista sorrindo olhando o celular</span>
                @endif
                <span class="s-float top-left">
                    <span class="s-float-icon">{!! $wa !!}</span>
                    <span><strong>Novo pedido #AL-1025</strong><small>chegou às 23:41</small></span>
                </span>
            </figure>
        </div>
    </section>

    {{-- Preço --}}
    @if ($plan)
        <section class="s-section" id="preco">
            <div class="s-wrap s-row">
                <div class="s-col s-text">
                    <span class="eyebrow">Preço</span>
                    <h2 class="s-title">Um plano, <span class="serif">tudo incluído</span></h2>
                    @if ($perDay)
                        <p>Menos de {{ Format::money($perDay) }} por dia. Uma venda no mês já paga a sua vitrine.</p>
                    @endif
                    @if ($trialDays)
                        <div class="s-guarantee"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg><span><strong>Teste {{ $trialDays }} dias sem cartão.</strong> Se não gostar, é só não assinar.</span></div>
                    @endif
                </div>
                <div class="s-col">
                    <div class="s-price-card">
                        <div class="row-between"><strong style="font-size: 18px">Plano {{ $plan->name }}</strong>@if ($trialDays)<span class="s-badge" style="background: var(--ink); color: #fff">{{ $trialDays }} dias grátis</span>@endif</div>
                        <div class="s-price"><strong>{{ Format::money($plan->price) }}</strong><span class="muted">/mês</span></div>
                        <ul class="s-list">
                            <li>{!! $check !!} Vitrine online com endereço próprio</li>
                            <li>{!! $check !!} {{ $plan->max_products ? 'Até '.$plan->max_products.' produtos' : 'Produtos ilimitados' }} com fotos por cor</li>
                            <li>{!! $check !!} Sacola, pedidos pelo WhatsApp e avisos de pedido</li>
                            <li>{!! $check !!} Banners, categorias, favoritos e busca</li>
                            <li>{!! $check !!} Sem taxa por venda, sem fidelidade</li>
                        </ul>
                        <a href="{{ url('painel/register') }}" class="btn btn-dark btn-lg btn-block">{{ $trialDays ? 'Começar '.$trialDays.' dias grátis' : 'Começar agora' }}</a>
                        <span class="small muted center">{{ $trialDays ? 'Depois, pagamento' : 'Pagamento' }} por Pix, boleto ou cartão.</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Dúvidas --}}
    <section class="s-section s-alt" id="duvidas">
        <div class="s-wrap" style="max-width: 880px">
            <h2 class="s-title" style="margin-bottom: 24px">Dúvidas <span class="serif">frequentes</span></h2>
            <div class="s-faq">
                <details open><summary>Preciso de CNPJ?</summary><p>Não. Você pode assinar com CPF ou CNPJ.</p></details>
                <details><summary>O cliente paga pelo site?</summary><p>Não. O Provador é um catálogo: o cliente monta a sacola e envia o pedido pelo WhatsApp. Pagamento e entrega são combinados direto com você.</p></details>
                <details><summary>O que acontece depois do teste grátis?</summary><p>Você assina para manter a vitrine no ar. Se não assinar, a vitrine sai do ar, mas seus produtos e pedidos continuam salvos para quando voltar.</p></details>
                <details><summary>Posso cancelar quando quiser?</summary><p>Sim, pelo próprio painel. A vitrine fica no ar até o fim do período já pago.</p></details>
                <details><summary>Funciona para outros segmentos além de roupas?</summary><p>Sim. Calçados, acessórios, cosméticos, decoração: qualquer loja que vende pelo WhatsApp.</p></details>
            </div>
        </div>
    </section>

    {{-- Chamada final --}}
    <section style="padding-block: 64px">
        <div class="s-wrap">
            <div class="s-cta">
                <div>
                    <h2>Comece <span class="serif">hoje</span> e receba o primeiro pedido pronto</h2>
                    <p>{{ $trialDays ? $trialDays.' dias grátis, sem cartão. ' : '' }}Sua vitrine no ar em minutos.</p>
                </div>
                <a href="{{ url('painel/register') }}" class="btn btn-lg s-btn-cream">{{ $trialDays ? 'Criar minha loja grátis' : 'Criar minha loja' }} {!! $arrow !!}</a>
            </div>
        </div>
    </section>

    <script>
        // "Veja funcionando": troca a tela do celular a cada 4s ou ao tocar num passo.
        (function () {
            var demo = document.querySelector('[data-demo]');
            if (!demo) return;
            var steps = demo.querySelectorAll('[data-step]');
            var panes = demo.querySelectorAll('[data-pane]');
            var current = 0, timer = null;
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function show(i) {
                current = i;
                steps.forEach(function (b, n) { b.setAttribute('aria-pressed', n === i ? 'true' : 'false'); });
                panes.forEach(function (p, n) { p.hidden = n !== i; });
            }
            steps.forEach(function (b, n) {
                b.addEventListener('click', function () { clearInterval(timer); show(n); });
            });
            if (!reduce) {
                timer = setInterval(function () { show((current + 1) % steps.length); }, 4000);
            }
        })();
    </script>
</x-site.layout>
