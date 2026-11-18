<?php

namespace App\Filament\Painel\Pages\Tenancy;

use App\Filament\Shared\StoreFields;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * "Configurações da loja": dados que aparecem na vitrine e na página Sobre.
 */
class EditStoreProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configurações da loja';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make('Loja')
                            ->schema([
                                StoreFields::name(),
                                StoreFields::slug()
                                    ->helperText('Atenção: ao mudar o endereço, o link antigo da vitrine deixa de funcionar.'),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn () => 'stores/'.Filament::getTenant()->getKey())
                                    ->maxSize(5120)
                                    ->automaticallyResizeImagesMode('contain')
                                    ->automaticallyResizeImagesToWidth('600')
                                    ->automaticallyResizeImagesToHeight('600')
                                    ->automaticallyUpscaleImagesWhenResizing(false),
                                Textarea::make('description')
                                    ->label('Sobre a loja')
                                    ->rows(4)
                                    ->maxLength(1000),
                            ]),
                        Tab::make('Contato e atendimento')
                            ->schema([
                                StoreFields::whatsapp(),
                                TextInput::make('instagram')
                                    ->label('Instagram')
                                    ->prefix('@')
                                    ->maxLength(60),
                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email(),
                                TextInput::make('opening_hours')
                                    ->label('Horário de atendimento')
                                    ->placeholder('Seg. a sex., 9h às 19h · Sáb., 9h às 14h'),
                                Section::make('Endereço')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('address')->label('Rua e número')->columnSpan(3),
                                        TextInput::make('city')->label('Cidade')->columnSpan(2),
                                        TextInput::make('state')->label('UF')->maxLength(2),
                                    ]),
                            ]),
                        Tab::make('Políticas')
                            ->schema([
                                Textarea::make('delivery_info')
                                    ->label('Entrega')
                                    ->placeholder('Ex.: Correios, motoboy na região, retirada na loja.')
                                    ->rows(3),
                                Textarea::make('payment_info')
                                    ->label('Pagamento')
                                    ->placeholder('Ex.: Pix, cartão via link, dinheiro na retirada.')
                                    ->rows(3),
                                Textarea::make('exchange_policy')
                                    ->label('Trocas e devoluções')
                                    ->rows(5),
                            ]),
                        Tab::make('Pedidos')
                            ->schema([
                                Toggle::make('notify_new_orders')
                                    ->label('Receber aviso de pedido novo por e-mail')
                                    ->helperText('Vai para o e-mail da aba "Contato e atendimento". Se estiver vazio, para o e-mail de quem é dono(a) da loja. O aviso no sininho do painel chega sempre.'),
                                TextInput::make('order_prefix')
                                    ->label('Prefixo do código do pedido')
                                    ->helperText('Aparece no código enviado pelo cliente, ex.: AL-1042.')
                                    ->required()
                                    ->alpha()
                                    ->maxLength(6)
                                    ->dehydrateStateUsing(fn (?string $state) => strtoupper((string) $state)),
                                Textarea::make('order_message_template')
                                    ->label('Mensagem de abertura no WhatsApp')
                                    ->placeholder('Olá, {loja}! Sou {cliente} e quero fazer o pedido {codigo}:')
                                    ->helperText('Variáveis: {loja}, {cliente}, {codigo}. Os itens e o total entram logo abaixo.')
                                    ->rows(2),
                            ]),
                    ]),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        // Se o endereço mudou, a URL atual do painel também mudou.
        return $this->tenant->wasChanged('slug') ? Filament::getUrl($this->tenant) : null;
    }
}
