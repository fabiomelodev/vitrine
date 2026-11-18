<?php

namespace App\Filament\Painel\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Itens')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('product_name')->label('Produto')->columnSpan(2),
                                        TextEntry::make('variant')
                                            ->label('Variação')
                                            ->state(fn ($record) => $record->variantLabel() ?: '—'),
                                        TextEntry::make('total')
                                            ->label('Total')
                                            ->money('BRL')
                                            ->helperText(fn ($record) => $record->quantity.' × R$ '.number_format((float) $record->unit_price, 2, ',', '.')),
                                    ]),
                            ]),
                        Section::make('Mensagem enviada pelo cliente')
                            ->collapsed()
                            ->schema([
                                TextEntry::make('whatsapp_message')
                                    ->hiddenLabel()
                                    ->placeholder('—')
                                    ->extraAttributes(['style' => 'white-space: pre-line']),
                            ]),
                    ]),
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Cliente')
                            ->schema([
                                TextEntry::make('customer_name')->label('Nome'),
                                TextEntry::make('customer_phone')
                                    ->label('WhatsApp')
                                    ->state(fn (Order $record) => $record->formattedPhone())
                                    ->url(fn (Order $record) => $record->customerWhatsappUrl(), shouldOpenInNewTab: true),
                                TextEntry::make('created_at')->label('Enviado em')->dateTime('d/m/Y H:i'),
                            ]),
                        Section::make('Valores')
                            ->schema([
                                TextEntry::make('subtotal')->label('Subtotal')->money('BRL'),
                                TextEntry::make('discount')->label('Desconto')->money('BRL'),
                                TextEntry::make('total')->label('Total estimado')->money('BRL')->weight('bold'),
                            ]),
                        Section::make('Atendimento')
                            ->schema([
                                ToggleButtons::make('status')
                                    ->label('Status')
                                    ->options(OrderStatus::class)
                                    ->required()
                                    ->columns(2),
                                Textarea::make('internal_notes')
                                    ->label('Observação interna')
                                    ->placeholder('Ex.: cliente vai retirar na loja sábado')
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }
}
