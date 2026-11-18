<?php

namespace App\Filament\Admin\Resources\Stores\Schemas;

use App\Filament\Shared\StoreFields;
use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Loja')
                    ->columns(2)
                    ->schema([
                        StoreFields::name(),
                        StoreFields::slug(),
                        StoreFields::whatsapp(),
                        Toggle::make('is_active')
                            ->label('Loja ativa')
                            ->helperText('Desligada: vitrine fora do ar e painel bloqueado para o lojista.')
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make('Assinatura')
                    ->columns(3)
                    ->schema([
                        Select::make('plan_id')
                            ->label('Plano')
                            ->relationship('plan', 'name')
                            ->preload(),
                        Select::make('subscription_status')
                            ->label('Status')
                            ->options(SubscriptionStatus::class)
                            ->default(SubscriptionStatus::Trialing->value)
                            ->helperText('"Cortesia" libera a loja sem cobrança. Os demais status são atualizados pelo Asaas.')
                            ->required(),
                        DateTimePicker::make('trial_ends_at')
                            ->label('Fim do teste')
                            ->seconds(false),
                        DateTimePicker::make('access_until')
                            ->label('Acesso pago até')
                            ->seconds(false),
                        TextEntry::make('asaas_subscription_id')
                            ->label('Assinatura no Asaas')
                            ->placeholder('—'),
                        TextEntry::make('billing_document')
                            ->label('CPF/CNPJ de cobrança')
                            ->placeholder('—'),
                    ]),
                Section::make('Responsáveis')
                    ->schema([
                        Select::make('users')
                            ->label('Usuários com acesso ao painel da loja')
                            ->relationship('users', 'email')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
