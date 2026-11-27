<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Stores\StoreResource;
use App\Models\Store;
use App\Support\PlatformMetrics;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Lojas em atraso ou que saíram do ar nos últimos 30 dias.
 */
class AttentionTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Precisam de atenção')
            ->description('Em atraso ou fora do ar nos últimos 30 dias. Para dar mais prazo a uma loja, abra e ajuste "Acesso pago até" ou "Fim do teste".')
            ->query(fn () => PlatformMetrics::attentionQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Loja')
                    ->description(fn (Store $record) => '/'.$record->slug),
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (Store $record) => PlatformMetrics::LABELS[PlatformMetrics::classify($record)])
                    ->color(fn (Store $record) => match (PlatformMetrics::classify($record)) {
                        PlatformMetrics::OFFLINE => 'danger',
                        PlatformMetrics::OVERDUE => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('bloqueio')
                    ->label('Sai / saiu do ar')
                    ->state(fn (Store $record) => $record->blocksAt()?->format('d/m/Y') ?? '—'),
                TextColumn::make('users.email')
                    ->label('Responsáveis')
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('billing_email')
                    ->label('E-mail de cobrança')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleOvalLeft)
                    ->color('success')
                    ->visible(fn (Store $record) => filled($record->whatsapp))
                    ->url(fn (Store $record) => $record->whatsappUrl('Olá! Aqui é do Provador. Notamos uma pendência na assinatura da '.$record->name.'. Posso ajudar?'), shouldOpenInNewTab: true),
                Action::make('editar')
                    ->label('Abrir')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(fn (Store $record) => StoreResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nenhuma loja em atraso ou fora do ar');
    }
}
