<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Stores\StoreResource;
use App\Models\Store;
use App\Support\PlatformMetrics;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Testes grátis que terminam nos próximos 7 dias, sem assinatura.
 */
class TrialsEndingTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Testes terminando nos próximos 7 dias')
            ->description('Lojas que ainda não assinaram. Quem cadastrou produtos e recebeu pedidos tem mais chance de assinar: vale um contato.')
            ->query(fn () => PlatformMetrics::trialsEndingQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Loja')
                    ->description(fn (Store $record) => '/'.$record->slug),
                TextColumn::make('users.email')
                    ->label('Responsáveis')
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('products_count')
                    ->label('Produtos')
                    ->counts('products'),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->counts('orders'),
                TextColumn::make('trial_ends_at')
                    ->label('Teste termina')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleOvalLeft)
                    ->color('success')
                    ->visible(fn (Store $record) => filled($record->whatsapp))
                    ->url(fn (Store $record) => $record->whatsappUrl('Olá! Aqui é do Provador. Vi que o teste da '.$record->name.' termina em breve. Posso ajudar em algo para você continuar com a vitrine?'), shouldOpenInNewTab: true),
                Action::make('maisDias')
                    ->label('+7 dias')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Estender o teste em 7 dias?')
                    ->modalDescription(fn (Store $record) => 'O teste da '.$record->name.' passa a terminar em '.$record->trial_ends_at?->copy()->addDays(7)->format('d/m/Y').'.')
                    ->action(function (Store $record) {
                        $record->forceFill(['trial_ends_at' => $record->trial_ends_at->copy()->addDays(7)])->save();
                        Notification::make()->title('Teste estendido até '.$record->trial_ends_at->format('d/m/Y'))->success()->send();
                    }),
                Action::make('editar')
                    ->label('Abrir')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(fn (Store $record) => StoreResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nenhum teste terminando nos próximos 7 dias');
    }
}
