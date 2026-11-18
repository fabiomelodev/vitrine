<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_company_data_is_optional_on_site_pages(): void
    {
        $this->get('/')->assertOk()->assertDontSee('CNPJ')->assertDontSee('[RAZÃO SOCIAL]');
        $this->get('/termos')->assertOk()->assertSee('plataforma de catálogo online, com sede');
    }

    public function test_company_data_appears_when_filled(): void
    {
        Setting::set('company_name', 'Provador Tecnologia Ltda.');
        Setting::set('company_document', '11222333000181');

        $this->get('/')->assertSee('Provador Tecnologia Ltda. · CNPJ 11.222.333/0001-81');
        $this->get('/termos')->assertSee('operada por Provador Tecnologia Ltda., CNPJ 11.222.333/0001-81');
        $this->get('/privacidade')->assertSee('(Provador Tecnologia Ltda., CNPJ 11.222.333/0001-81)');
    }

    public function test_only_company_name_without_cnpj(): void
    {
        Setting::set('company_name', 'Empresa Exemplo');

        $this->get('/')->assertSee('Empresa Exemplo')->assertDontSee('CNPJ');
    }

    public function test_settings_page_is_only_for_super_admin(): void
    {
        $admin = User::where('email', 'admin@provador.test')->firstOrFail();
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin/configuracoes')->assertOk()->assertSee('Razão social');
        $this->actingAs($owner)->get('/admin/configuracoes')->assertForbidden();
    }
}
