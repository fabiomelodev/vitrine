@php
    use App\Support\Format;

    $int = fn ($n) => number_format((float) $n, 0, ',', '.');
    $dec = fn ($n) => number_format((float) $n, 1, ',', '.');
@endphp
<x-filament-panels::page>
    <style>
        .pv-muted { opacity: .7; }
        .pv-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr)); }
        .pv-two { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(min(380px, 100%), 1fr)); align-items: start; }
        .pv-card { border-radius: .75rem; padding: 1rem 1.25rem; background: color-mix(in oklab, currentColor 3%, transparent); border: 1px solid color-mix(in oklab, currentColor 10%, transparent); display: flex; flex-direction: column; gap: .25rem; }
        .pv-card-label { font-size: .875rem; opacity: .75; }
        .pv-card-value { font-size: 1.75rem; font-weight: 600; line-height: 1.2; }
        .pv-card-hint { font-size: .8125rem; opacity: .7; }
        .pv-up { color: var(--success-600, #15803d); font-weight: 600; }
        .pv-down { color: var(--danger-600, #b91c1c); font-weight: 600; }
        .pv-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
        .pv-list li { display: flex; gap: .75rem; align-items: center; padding: .75rem 0; border-top: 1px solid color-mix(in oklab, currentColor 10%, transparent); }
        .pv-list li:first-child { border-top: 0; padding-top: 0; }
        .pv-rank { flex: 0 0 auto; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 600; background: color-mix(in oklab, currentColor 8%, transparent); }
        .pv-item { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .125rem; }
        .pv-item strong { font-weight: 600; font-size: .9375rem; overflow-wrap: anywhere; }
        .pv-item span { font-size: .8125rem; opacity: .7; }
        .pv-num { flex: 0 0 auto; text-align: right; font-size: .875rem; }
        .pv-num strong { display: block; font-size: 1rem; }
        .pv-bar { height: .375rem; border-radius: 999px; background: color-mix(in oklab, currentColor 8%, transparent); overflow: hidden; margin-top: .25rem; }
        .pv-bar i { display: block; height: 100%; border-radius: 999px; background: var(--primary-500, #78716c); }
        .pv-table-wrap { overflow-x: auto; }
        .pv-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .pv-table th { text-align: left; font-weight: 600; padding: .5rem .75rem .5rem 0; border-bottom: 1px solid color-mix(in oklab, currentColor 12%, transparent); white-space: nowrap; }
        .pv-table td { padding: .625rem .75rem .625rem 0; border-bottom: 1px solid color-mix(in oklab, currentColor 8%, transparent); }
        .pv-table .r { text-align: right; }
        .pv-alert { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; justify-content: space-between; padding: .875rem 1rem; border-radius: .75rem; background: #F6EAD7; color: #5C3A10; font-size: .875rem; }
        .pv-alert a { font-weight: 600; text-decoration: underline; color: inherit; }
        .pv-info { padding: .875rem 1rem; border-radius: .75rem; background: color-mix(in oklab, currentColor 5%, transparent); font-size: .875rem; }
        .pv-roi { display: flex; flex-wrap: wrap; gap: 1rem 2rem; align-items: center; justify-content: space-between; }
        .pv-roi-value { font-size: 2rem; font-weight: 600; line-height: 1.1; }
        .pv-empty { padding: 1.5rem 0; text-align: center; font-size: .875rem; opacity: .7; }
        .pv-funnel { display: flex; flex-direction: column; gap: .875rem; }
        .pv-funnel-row { display: grid; grid-template-columns: minmax(130px, 200px) 1fr auto; gap: .75rem; align-items: center; font-size: .875rem; }
        .pv-funnel-track { height: 1.75rem; border-radius: .5rem; background: color-mix(in oklab, currentColor 6%, transparent); overflow: hidden; }
        .pv-funnel-track i { display: block; height: 100%; min-width: 2px; border-radius: .5rem; background: var(--primary-500, #78716c); }
        .pv-funnel-num { min-width: 5.5rem; text-align: right; }
        .pv-funnel-num strong { font-size: 1rem; }
        .pv-chart { display: flex; align-items: flex-end; gap: 2px; height: 140px; padding-top: .5rem; }
        .pv-chart span { flex: 1; min-width: 2px; border-radius: 3px 3px 0 0; background: var(--primary-500, #78716c); }
        .pv-chart span.zero { background: color-mix(in oklab, currentColor 8%, transparent); }
        .pv-chart-axis { display: flex; justify-content: space-between; font-size: .75rem; opacity: .7; margin-top: .375rem; }
        .pv-link-row { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; justify-content: space-between; padding: .625rem 0; border-top: 1px solid color-mix(in oklab, currentColor 10%, transparent); font-size: .875rem; }
        .pv-link-row:first-child { border-top: 0; padding-top: 0; }
        .pv-link-row code { font-size: .8125rem; overflow-wrap: anywhere; opacity: .8; }
        @media (max-width: 640px) { .pv-funnel-row { grid-template-columns: 1fr auto; } .pv-funnel-track { grid-column: 1 / -1; order: 3; } }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem">
        {{-- Abas e período --}}
        <div style="display: flex; flex-wrap: wrap; gap: .75rem 1rem; align-items: center; justify-content: space-between">
            <x-filament::tabs label="Tipo de métrica">
                @foreach ($tabs as $key => $label)
                    <x-filament::tabs.item :active="$tab === $key" wire:click="setTab('{{ $key }}')">
                        {{ $label }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
            <div style="min-width: 220px">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="period" aria-label="Período">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        <span class="pv-muted" style="font-size: .875rem; margin-top: -.75rem">
            De {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}
        </span>

        {{-- Pedidos esquecidos --}}
        @if ($forgotten > 0)
            <div class="pv-alert" role="status">
                <span>
                    <strong>{{ $forgotten }} {{ $forgotten === 1 ? 'pedido está' : 'pedidos estão' }} em aberto há mais de {{ $forgottenDays }} dias.</strong>
                    Marque como Vendido ou Cancelado para as métricas mostrarem o que realmente vendeu.
                </span>
                <a href="{{ $ordersUrl }}">Ver pedidos</a>
            </div>
        @endif

        @if ($tab !== 'vendas' && (! $trackingSince || $trackingSince->greaterThan($from)))
            <div class="pv-info">
                @if ($trackingSince)
                    As visitas são contadas desde {{ $trackingSince->format('d/m/Y') }}. Períodos anteriores a essa data aparecem incompletos.
                @else
                    Ainda não há visitas registradas. Assim que clientes abrirem sua vitrine, os números aparecem aqui.
                @endif
                Visitas da sua equipe (logada no painel) não entram na conta.
            </div>
        @endif

        {{-- ===================== VENDAS ===================== --}}
        @if ($tab === 'vendas')
            <x-filament::section>
                <div class="pv-roi">
                    <div>
                        <div class="pv-card-label">Vendido pela vitrine neste mês</div>
                        <div class="pv-roi-value">{{ Format::money($soldThisMonth) }}</div>
                    </div>
                    @if ($planPrice)
                        <div style="font-size: .9375rem; max-width: 420px">
                            Sua assinatura custa <strong>{{ Format::money($planPrice) }}/mês</strong>.
                            @if ($paidTimes >= 1)
                                Neste mês, a vitrine já vendeu <strong>{{ $int($paidTimes) }} {{ $paidTimes == 1 ? 'vez' : 'vezes' }}</strong> o valor da assinatura.
                            @else
                                <span class="pv-muted">Marque os pedidos fechados como Vendido para acompanhar o retorno.</span>
                            @endif
                        </div>
                    @endif
                </div>
            </x-filament::section>

            @php
                $cards = [
                    ['Pedidos recebidos', $int($current['orders']), $changes['orders'], 'Sacolas enviadas pelo WhatsApp'],
                    ['Pedidos vendidos', $int($current['sold']), $changes['sold'], 'Marcados como Vendido'],
                    ['Valor vendido', Format::money($current['sold_total']), $changes['sold_total'], 'Soma dos pedidos vendidos'],
                    ['Ticket médio', $current['avg_ticket'] !== null ? Format::money($current['avg_ticket']) : '—', $changes['avg_ticket'], 'Valor médio por venda'],
                    ['Peças por venda', $current['pieces_per_order'] !== null ? $dec($current['pieces_per_order']) : '—', null, 'Média de peças em cada venda'],
                    ['Taxa de fechamento', $current['close_rate'] !== null ? $current['close_rate'].'%' : '—', null, $current['close_rate'] !== null ? 'De cada 10 pedidos, '.round($current['close_rate'] / 10).' viram venda' : 'Sem pedidos no período'],
                ];
            @endphp
            <div class="pv-grid">
                @foreach ($cards as [$label, $value, $change, $hint])
                    <div class="pv-card">
                        <span class="pv-card-label">{{ $label }}</span>
                        <span class="pv-card-value">{{ $value }}</span>
                        <span class="pv-card-hint">
                            @if ($change !== null)
                                <span class="{{ $change >= 0 ? 'pv-up' : 'pv-down' }}">{{ $change >= 0 ? '↑' : '↓' }} {{ abs($change) }}%</span> vs período anterior
                            @else
                                {{ $hint }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="pv-two">
                <x-filament::section heading="Mais pedidos" description="Possíveis vendas: todas as peças que entraram em pedidos, vendidas ou não.">
                    @if (count($topRequested))
                        @php $maxPieces = max(array_column($topRequested, 'pieces')) ?: 1; @endphp
                        <ol class="pv-list">
                            @foreach ($topRequested as $i => $p)
                                <li>
                                    <span class="pv-rank">{{ $i + 1 }}</span>
                                    <span class="pv-item">
                                        <strong>{{ $p['name'] }}</strong>
                                        <span>{{ $p['pieces'] }} {{ $p['pieces'] === 1 ? 'peça' : 'peças' }} em {{ $p['orders'] }} {{ $p['orders'] === 1 ? 'pedido' : 'pedidos' }}</span>
                                        <span class="pv-bar" aria-hidden="true"><i style="width: {{ round($p['pieces'] / $maxPieces * 100) }}%"></i></span>
                                    </span>
                                    <span class="pv-num">
                                        <strong>{{ $p['sold_pieces'] }} {{ $p['sold_pieces'] === 1 ? 'vendida' : 'vendidas' }}</strong>
                                        <span class="pv-muted">{{ $p['close_rate'] }}% fechou</span>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="pv-empty">Nenhum pedido no período.</div>
                    @endif
                </x-filament::section>

                <x-filament::section heading="Mais vendidos" description="Só pedidos que você marcou como Vendido.">
                    @if (count($topSold))
                        @php $maxSold = max(array_column($topSold, 'sold_pieces')) ?: 1; @endphp
                        <ol class="pv-list">
                            @foreach ($topSold as $i => $p)
                                <li>
                                    <span class="pv-rank">{{ $i + 1 }}</span>
                                    <span class="pv-item">
                                        <strong>{{ $p['name'] }}</strong>
                                        <span>{{ $p['sold_pieces'] }} {{ $p['sold_pieces'] === 1 ? 'peça vendida' : 'peças vendidas' }}</span>
                                        <span class="pv-bar" aria-hidden="true"><i style="width: {{ round($p['sold_pieces'] / $maxSold * 100) }}%"></i></span>
                                    </span>
                                    <span class="pv-num"><strong>{{ Format::money($p['sold_total']) }}</strong></span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="pv-empty">Nenhuma venda marcada no período. Abra os pedidos e mude o status para Vendido quando fechar a venda.</div>
                    @endif
                </x-filament::section>
            </div>

            <x-filament::section heading="Cores e tamanhos mais pedidos" description="Ajuda a decidir o que repor.">
                @if (count($topVariants))
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col">Cor</th>
                                    <th scope="col">Tamanho</th>
                                    <th scope="col" class="r">Pedidas</th>
                                    <th scope="col" class="r">Vendidas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topVariants as $v)
                                    <tr>
                                        <td>{{ $v['name'] }}</td>
                                        <td>{{ $v['color'] ?? '—' }}</td>
                                        <td>{{ $v['size'] ?? '—' }}</td>
                                        <td class="r">{{ $v['pieces'] }}</td>
                                        <td class="r">{{ $v['sold_pieces'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pv-empty">Nenhum pedido com cor ou tamanho no período.</div>
                @endif
            </x-filament::section>
        @endif

        {{-- ===================== VISITAS ===================== --}}
        @if ($tab === 'visitas')
            <div class="pv-grid">
                <div class="pv-card">
                    <span class="pv-card-label">Visitantes</span>
                    <span class="pv-card-value">{{ $int($visitors) }}</span>
                    <span class="pv-card-hint">
                        @if ($visitorsChange !== null)
                            <span class="{{ $visitorsChange >= 0 ? 'pv-up' : 'pv-down' }}">{{ $visitorsChange >= 0 ? '↑' : '↓' }} {{ abs($visitorsChange) }}%</span> vs período anterior
                        @else
                            Pessoas diferentes por dia
                        @endif
                    </span>
                </div>
                <div class="pv-card">
                    <span class="pv-card-label">Conversão em pedido</span>
                    <span class="pv-card-value">{{ $conversion !== null ? $dec($conversion).'%' : '—' }}</span>
                    <span class="pv-card-hint">Pedidos enviados ÷ visitantes</span>
                </div>
            </div>

            <x-filament::section heading="Funil da vitrine" description="Em que etapa os clientes desistem. A porcentagem é sobre o total de visitantes.">
                @if ($funnel[0]['value'] > 0)
                    <div class="pv-funnel">
                        @foreach ($funnel as $step)
                            <div class="pv-funnel-row">
                                <span>{{ $step['label'] }}</span>
                                <span class="pv-funnel-track" aria-hidden="true"><i style="width: {{ min(100, $step['rate'] ?? 0) }}%"></i></span>
                                <span class="pv-funnel-num"><strong>{{ $int($step['value']) }}</strong> <span class="pv-muted">{{ $step['rate'] }}%</span></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="pv-empty">Nenhuma visita no período.</div>
                @endif
            </x-filament::section>

            <x-filament::section heading="Visitantes por dia">
                @php $realMax = max(array_column($byDay, 'value') ?: [0]); $maxDay = $realMax ?: 1; $lastDay = $byDay[count($byDay) - 1]; @endphp
                <div class="pv-chart" role="img" aria-label="Gráfico de visitantes por dia">
                    @foreach ($byDay as $d)
                        <span class="{{ $d['value'] === 0 ? 'zero' : '' }}" style="height: {{ $d['value'] === 0 ? 2 : max(4, round($d['value'] / $maxDay * 100)) }}%" title="{{ $d['date']->format('d/m') }}: {{ $d['value'] }} {{ $d['value'] === 1 ? 'visitante' : 'visitantes' }}"></span>
                    @endforeach
                </div>
                <div class="pv-chart-axis">
                    <span>{{ $byDay[0]['date']->format('d/m') }}</span>
                    <span>Máximo: {{ $int($realMax) }} por dia</span>
                    <span>{{ $lastDay['date']->format('d/m') }}</span>
                </div>
            </x-filament::section>

            <div class="pv-two">
                <x-filament::section heading="De onde vêm os clientes" description="Visitantes e pedidos por origem.">
                    @if (count($sources))
                        @php $maxSrc = max(array_column($sources, 'visitors')) ?: 1; @endphp
                        <ul class="pv-list">
                            @foreach ($sources as $s)
                                <li>
                                    <span class="pv-item">
                                        <strong>{{ $s['label'] }}</strong>
                                        <span class="pv-bar" aria-hidden="true"><i style="width: {{ round($s['visitors'] / $maxSrc * 100) }}%"></i></span>
                                    </span>
                                    <span class="pv-num">
                                        <strong>{{ $int($s['visitors']) }} {{ $s['visitors'] === 1 ? 'visitante' : 'visitantes' }}</strong>
                                        <span class="pv-muted">{{ $s['orders'] }} {{ $s['orders'] === 1 ? 'pedido' : 'pedidos' }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="pv-empty">Nenhuma visita no período.</div>
                    @endif
                </x-filament::section>

                <x-filament::section heading="Links para divulgar" description="Use um link para cada canal e descubra qual traz mais clientes. Todos abrem a sua vitrine normalmente.">
                    <div>
                        @foreach ($shareLinks as [$label, $link])
                            <div class="pv-link-row" x-data="{ copied: false }">
                                <span style="display: flex; flex-direction: column; gap: .125rem; min-width: 0">
                                    <strong style="font-weight: 600">{{ $label }}</strong>
                                    <code>{{ $link }}</code>
                                </span>
                                <x-filament::button size="sm" color="gray" outlined
                                    x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($link) }}); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-text="copied ? 'Copiado!' : 'Copiar'">Copiar</span>
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- ===================== PRODUTOS E PROCURA ===================== --}}
        @if ($tab === 'produtos')
            <x-filament::section heading="Mais vistos" description="Quantas pessoas abriram cada produto e quantos pedidos ele teve. Muita visita e pouco pedido pode indicar preço, foto ou falta de tamanho.">
                @if (count($mostViewed))
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col" class="r">Visitantes</th>
                                    <th scope="col" class="r">Pedidos</th>
                                    <th scope="col" class="r">Conversão</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mostViewed as $p)
                                    <tr>
                                        <td>
                                            {{ $p['name'] }}
                                            @if ($p['views'] >= 20 && $p['rate'] < 2)
                                                <x-filament::badge color="warning" size="sm" style="display: inline-flex; margin-left: .375rem">Olham, mas não levam</x-filament::badge>
                                            @endif
                                        </td>
                                        <td class="r">{{ $int($p['views']) }}</td>
                                        <td class="r">{{ $p['orders'] }}</td>
                                        <td class="r">{{ $p['rate'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pv-empty">Nenhum produto visto no período.</div>
                @endif
            </x-filament::section>

            <div class="pv-two">
                <x-filament::section heading="Mais favoritados" description="Peças que os clientes guardaram no coração: sinal de desejo, mesmo sem pedido.">
                    @if (count($favorites))
                        <ol class="pv-list">
                            @foreach ($favorites as $i => $f)
                                <li>
                                    <span class="pv-rank">{{ $i + 1 }}</span>
                                    <span class="pv-item"><strong>{{ $f['name'] }}</strong></span>
                                    <span class="pv-num"><strong>{{ $f['total'] }}</strong> <span class="pv-muted">{{ $f['total'] === 1 ? 'pessoa' : 'pessoas' }}</span></span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="pv-empty">Nenhum favorito no período.</div>
                    @endif
                </x-filament::section>

                <x-filament::section heading="Produtos parados" description="Na vitrine, mas sem nenhuma visita e nenhum pedido no período. Vale trocar a foto, fazer promoção ou destacar no Instagram.">
                    @if ($stalled['count'] > 0)
                        <p style="margin: 0 0 .75rem; font-size: .875rem"><strong>{{ $stalled['count'] }} {{ $stalled['count'] === 1 ? 'produto parado' : 'produtos parados' }}</strong>{{ $stalled['count'] > count($stalled['items']) ? ' (mostrando os mais antigos)' : '' }}</p>
                        <ul class="pv-list">
                            @foreach ($stalled['items'] as $name)
                                <li><span class="pv-item"><strong>{{ $name }}</strong></span></li>
                            @endforeach
                        </ul>
                    @else
                        <div class="pv-empty">Nenhum produto parado. Todos tiveram visita ou pedido.</div>
                    @endif
                </x-filament::section>
            </div>

            <div class="pv-two">
                <x-filament::section heading="Buscas sem resultado" description="O que os clientes procuraram e não encontraram na sua vitrine: ideias para as próximas compras.">
                    @if (count($failedSearches))
                        <ol class="pv-list">
                            @foreach ($failedSearches as $i => $s)
                                <li>
                                    <span class="pv-rank">{{ $i + 1 }}</span>
                                    <span class="pv-item"><strong>“{{ $s['term'] }}”</strong></span>
                                    <span class="pv-num"><strong>{{ $s['total'] }}</strong> <span class="pv-muted">{{ $s['total'] === 1 ? 'pessoa' : 'pessoas' }}</span></span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="pv-empty">Nenhuma busca sem resultado no período.</div>
                    @endif
                </x-filament::section>

                <x-filament::section heading="Mais buscados">
                    @if (count($topSearches))
                        <ol class="pv-list">
                            @foreach ($topSearches as $i => $s)
                                <li>
                                    <span class="pv-rank">{{ $i + 1 }}</span>
                                    <span class="pv-item"><strong>“{{ $s['term'] }}”</strong></span>
                                    <span class="pv-num"><strong>{{ $s['total'] }}</strong> <span class="pv-muted">{{ $s['total'] === 1 ? 'pessoa' : 'pessoas' }}</span></span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="pv-empty">Nenhuma busca no período.</div>
                    @endif
                </x-filament::section>
            </div>

            <x-filament::section heading="Procura por esgotados" description="Vendas que você pode estar perdendo: clientes que tocaram num tamanho esgotado ou pediram aviso de reposição.">
                @if (count($soldOut))
                    <div class="pv-table-wrap">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col">Cor / tamanho</th>
                                    <th scope="col" class="r">Tocaram</th>
                                    <th scope="col" class="r">Pediram aviso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($soldOut as $s)
                                    <tr>
                                        <td>{{ $s['name'] }}</td>
                                        <td>{{ $s['variant'] ?? 'Peça toda' }}</td>
                                        <td class="r">{{ $s['taps'] }}</td>
                                        <td class="r">{{ $s['requests'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pv-empty">Ninguém procurou peças esgotadas no período.</div>
                @endif
            </x-filament::section>

            <div class="pv-card" style="max-width: 420px">
                <span class="pv-card-label">Peças colocadas na sacola pelo “Complete o look”</span>
                <span class="pv-card-value">{{ $int($suggestionAdds) }}</span>
                <span class="pv-card-hint">Sugestões que aparecem na sacola do cliente.</span>
            </div>
        @endif
    </div>
</x-filament-panels::page>
