@php use App\Support\Format; @endphp
<x-site.layout>
    <section class="hero container" style="padding-bottom: 40px">
        <h1 class="hero-title">Sua loja com <span class="serif">vitrine online</span><br>e pedidos no WhatsApp</h1>
        <p class="hero-sub" style="max-width: 600px">Cadastre suas peças com fotos, cores e tamanhos. Seu cliente monta a sacola e o pedido chega pronto no seu WhatsApp. Sem checkout, sem taxa por venda.</p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 28px">
            <a href="{{ url('painel/register') }}" class="btn btn-dark btn-lg">Criar minha loja grátis</a>
            @if ($plan?->trial_days)
                <span class="small muted" style="align-self: center">{{ $plan->trial_days }} dias grátis · sem cartão</span>
            @endif
        </div>
    </section>

    <section class="section section-alt" id="como-funciona">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 28px">Como <span class="serif">funciona</span></h2>
            <ol class="steps">
                <li><span class="num">01</span><strong>Cadastre sua loja</strong><span class="muted small">Em 2 minutos: nome, endereço da vitrine e o WhatsApp que recebe os pedidos.</span></li>
                <li><span class="num">02</span><strong>Monte o catálogo</strong><span class="muted small">Produtos com fotos por cor, grade de tamanhos e marcação do que esgotou.</span></li>
                <li><span class="num">03</span><strong>Divulgue o link</strong><span class="muted small">No Instagram, no status e nos grupos. Ex.: {{ str_replace(['https://', 'http://'], '', url('sualoja')) }}</span></li>
                <li><span class="num" style="color: var(--green)">04</span><strong>Receba no WhatsApp</strong><span class="muted small">O cliente envia a sacola com código do pedido. Você combina frete e pagamento como já faz.</span></li>
            </ol>
        </div>
    </section>

    <section class="section container">
        <h2 class="section-title" style="margin-bottom: 28px">Tudo que sua loja <span class="serif">precisa</span></h2>
        <div class="info-cards">
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Vitrine com a sua cara</strong><span class="muted small">Logo, banners em carrossel, categorias e página "Sobre a loja" com trocas e horários.</span></div>
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Fotos por cor</strong><span class="muted small">O cliente clica na cor e vê as fotos dela. Tamanhos esgotados aparecem desabilitados.</span></div>
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Sacola e "Complete o look"</strong><span class="muted small">O cliente leva mais peças num pedido só, com sugestões que você escolhe.</span></div>
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Pedidos organizados</strong><span class="muted small">Cada pedido com código, itens e total. Aviso no painel e por e-mail quando chega um novo.</span></div>
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Sem taxa por venda</strong><span class="muted small">Você recebe direto do cliente, do jeito que já trabalha: Pix, cartão ou dinheiro.</span></div>
            <div class="info-card" style="background: var(--card); border: 1px solid #E3DED7"><strong>Feito para o celular</strong><span class="muted small">Vitrine rápida no celular do cliente e painel simples para você gerenciar.</span></div>
        </div>
    </section>

    @if ($plan)
        <section class="section section-alt" id="preco">
            <div class="container" style="display: flex; flex-direction: column; align-items: center; gap: 20px; text-align: center">
                <h2 class="section-title">Um plano, <span class="serif">tudo incluído</span></h2>
                <div class="card-box" style="max-width: 420px; width: 100%; align-items: center; padding: 32px">
                    <strong style="font-size: 18px">Plano {{ $plan->name }}</strong>
                    <div><span style="font-size: 44px; font-weight: 600">{{ Format::money($plan->price) }}</span><span class="muted">/mês</span></div>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; text-align: left" class="small">
                        <li>✓ Vitrine online com endereço próprio</li>
                        <li>✓ {{ $plan->max_products ? 'Até '.$plan->max_products.' produtos' : 'Produtos ilimitados' }} com fotos por cor</li>
                        <li>✓ Sacola, pedidos pelo WhatsApp e avisos de pedido</li>
                        <li>✓ Banners, categorias, favoritos e busca</li>
                        <li>✓ Sem taxa por venda · cancele quando quiser</li>
                    </ul>
                    <a href="{{ url('painel/register') }}" class="btn btn-dark btn-lg btn-block">Começar {{ $plan->trial_days ? $plan->trial_days.' dias grátis' : 'agora' }}</a>
                    <span class="small muted">Pagamento por Pix, boleto ou cartão.</span>
                </div>
            </div>
        </section>
    @endif

    <section class="section" id="duvidas">
        <div class="container" style="max-width: 880px">
            <h2 class="section-title" style="margin-bottom: 20px">Dúvidas <span class="serif">frequentes</span></h2>
            <div class="details">
                <details><summary>Preciso de CNPJ?</summary><p>Não. Você pode assinar com CPF ou CNPJ.</p></details>
                <details><summary>O cliente paga pelo site?</summary><p>Não. O Provador é um catálogo: o cliente monta a sacola e envia o pedido pelo WhatsApp. Pagamento e entrega são combinados direto com você.</p></details>
                <details><summary>O que acontece depois do teste grátis?</summary><p>Você assina para manter a vitrine no ar. Se não assinar, a vitrine sai do ar, mas seus produtos e pedidos continuam salvos para quando voltar.</p></details>
                <details><summary>Posso cancelar quando quiser?</summary><p>Sim, pelo próprio painel. A vitrine fica no ar até o fim do período já pago.</p></details>
                <details><summary>Funciona para outros segmentos além de roupas?</summary><p>Sim. Calçados, acessórios, cosméticos, decoração — qualquer loja que vende por WhatsApp.</p></details>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container center stack" style="align-items: center">
            <h2 class="section-title">Comece <span class="serif">hoje</span></h2>
            <a href="{{ url('painel/register') }}" class="btn btn-dark btn-lg">Criar minha loja grátis</a>
        </div>
    </section>
</x-site.layout>
