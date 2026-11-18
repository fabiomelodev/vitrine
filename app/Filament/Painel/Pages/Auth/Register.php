<?php

namespace App\Filament\Painel\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Cadastro do lojista com aceite dos Termos de uso e da Política de privacidade.
 */
class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                Checkbox::make('terms')
                    ->label(new HtmlString(
                        'Li e aceito os <a href="'.e(url('termos')).'" target="_blank" style="text-decoration: underline">Termos de uso</a>'
                        .' e a <a href="'.e(url('privacidade')).'" target="_blank" style="text-decoration: underline">Política de privacidade</a>.'
                    ))
                    ->rule('accepted')
                    ->validationMessages(['accepted' => 'Para criar a conta, aceite os termos.']),
            ]);
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        unset($data['terms']);
        $data['terms_accepted_at'] = now();

        return $data;
    }
}
