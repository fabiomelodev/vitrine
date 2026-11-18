<?php

namespace App\Filament\Shared;

use App\Models\Store;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Campos da loja reaproveitados no cadastro, no perfil da loja e no admin.
 */
class StoreFields
{
    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label('Nome da loja')
            ->required()
            ->maxLength(80)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                // Só sugere o endereço enquanto o lojista não mexeu nele.
                if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label('Endereço da vitrine')
            ->prefix(rtrim(url('/'), '/').'/')
            ->helperText('Só letras minúsculas, números e hífen. É o link que você vai divulgar.')
            ->required()
            ->minLength(3)
            ->maxLength(50)
            ->alphaDash()
            ->notIn(Store::RESERVED_SLUGS)
            ->unique(Store::class, 'slug', ignoreRecord: true)
            ->dehydrateStateUsing(fn (?string $state) => Str::slug((string) $state))
            ->validationMessages([
                'not_in' => 'Este endereço é reservado. Escolha outro.',
                'unique' => 'Já existe uma loja com este endereço.',
            ]);
    }

    public static function whatsapp(): TextInput
    {
        return TextInput::make('whatsapp')
            ->label('WhatsApp que recebe os pedidos')
            ->tel()
            ->mask('(99) 99999-9999')
            ->placeholder('(11) 91234-5678')
            ->required()
            ->regex('/^\(\d{2}\) 9\d{4}-\d{4}$/')
            ->formatStateUsing(fn (?string $state) => static::formatPhone($state))
            ->validationMessages([
                'regex' => 'Informe um celular com DDD, ex.: (11) 91234-5678.',
            ]);
    }

    public static function formatPhone(?string $value): ?string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        if (str_starts_with($d, '55') && strlen($d) === 13) {
            $d = substr($d, 2);
        }

        return strlen($d) === 11
            ? sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7))
            : $value;
    }
}
