@php
    $store = \Filament\Facades\Filament::getTenant();
@endphp
@if ($store instanceof \App\Models\Store && ! request()->routeIs('*assinatura*'))
    @php
        $status = $store->subscription_status;
        $days = $store->trialDaysLeft();
        $url = url('painel/'.$store->slug.'/assinatura');
    @endphp
    @if ($status === \App\Enums\SubscriptionStatus::Trialing && ! $store->asaas_subscription_id && $days !== null)
        <div style="margin-top: 1.5rem; margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; background: #F6EAD7; color: #5C3A10; display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; justify-content: space-between; font-size: .875rem">
            <span>Seu período de teste termina {{ $days === 0 ? 'hoje' : ($days === 1 ? 'amanhã' : "em {$days} dias") }}. Assine para manter sua vitrine no ar.</span>
            <a href="{{ $url }}" style="font-weight: 600; text-decoration: underline">Assinar agora</a>
        </div>
    @elseif ($status === \App\Enums\SubscriptionStatus::PastDue || $store->paidPeriodOverdue())
        @php $blocksAt = $store->blocksAt(); @endphp
        <div style="margin-top: 1.5rem; margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; background: #FBF1EF; color: #8A2F22; display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; justify-content: space-between; font-size: .875rem">
            <span>Há um pagamento em atraso. {{ $blocksAt ? 'Sua vitrine sai do ar em '.$blocksAt->format('d/m/Y').' se não for pago.' : 'Regularize para sua vitrine não sair do ar.' }}</span>
            <a href="{{ $url }}" style="font-weight: 600; text-decoration: underline">Ver pagamento</a>
        </div>
    @endif
@endif
