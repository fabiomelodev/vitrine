<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use App\Support\Document;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Configurações da plataforma (super admin): dados da empresa, todos opcionais.
 */
class PlatformSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $title = 'Configurações da plataforma';

    protected static ?string $slug = 'configuracoes';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.admin.pages.platform-settings';

    public string $company_name = '';

    public string $company_document = '';

    public function mount(): void
    {
        $this->company_name = (string) Setting::get('company_name', '');
        $this->company_document = (string) Setting::get('company_document', '');
    }

    public function save(): void
    {
        $this->validate([
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_document' => ['nullable', 'string', 'max:20'],
        ]);

        $cnpj = Document::digits($this->company_document);

        if ($cnpj !== '' && (strlen($cnpj) !== 14 || ! Document::isValid($cnpj))) {
            throw ValidationException::withMessages(['company_document' => 'CNPJ inválido.']);
        }

        Setting::set('company_name', $this->company_name);
        Setting::set('company_document', $cnpj ?: null);

        $this->company_document = $cnpj;

        Notification::make()->title('Configurações salvas')->success()->send();
    }
}
