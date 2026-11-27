<?php

namespace App\Filament\Admin\Widgets;

use App\Models\SubscriptionPayment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Últimos pagamentos de assinatura confirmados.
 */
class RecentPaymentsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Últimos pagamentos')
            ->query(fn () => SubscriptionPayment::query()
                ->with('store')
                ->whereIn('status', SubscriptionPayment::PAID_STATUSES)
                ->orderByDesc('paid_at')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('store.name')->label('Loja'),
                TextColumn::make('value')->label('Valor')->money('BRL'),
                TextColumn::make('billing_type')
                    ->label('Forma')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'PIX' => 'Pix',
                        'BOLETO' => 'Boleto',
                        'CREDIT_CARD' => 'Cartão',
                        default => $state ?? '—',
                    }),
                TextColumn::make('due_date')->label('Vencimento')->date('d/m/Y'),
                TextColumn::make('paid_at')->label('Pago em')->date('d/m/Y'),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nenhum pagamento ainda');
    }
}
