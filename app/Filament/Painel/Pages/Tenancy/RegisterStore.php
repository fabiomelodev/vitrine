<?php

namespace App\Filament\Painel\Pages\Tenancy;

use App\Enums\StoreRole;
use App\Filament\Shared\StoreFields;
use App\Models\Store;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Primeira tela do lojista depois de criar a conta: cadastrar a loja.
 */
class RegisterStore extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar minha loja';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                StoreFields::name(),
                StoreFields::slug(),
                StoreFields::whatsapp(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $store = Store::create($data);

        $store->users()->attach(auth()->user(), ['role' => StoreRole::Owner->value]);

        return $store;
    }
}
