<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Configuracao\AbaEmail;
use HT2ML\Core\Livewire\Admin\Configuracao\AbaLocalizacao;
use HT2ML\Core\Livewire\Admin\Configuracao\AbaSeguranca;
use HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario;
use HT2ML\Core\Settings\EmailSettings;
use HT2ML\Core\Settings\LocalizacaoSettings;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('prefs@config.test');
    $this->admin->assignRole('super-admin');
});

it('salva configurações de e-mail', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaEmail::class)
        ->set('smtp_host', 'smtp.exemplo.com')
        ->set('smtp_port', 587)
        ->set('from_email', 'nao-responda@exemplo.com')
        ->set('habilitar', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = app(EmailSettings::class);

    expect($settings->smtp_host)->toBe('smtp.exemplo.com')
        ->and($settings->habilitar)->toBeTrue();
});

it('envia e-mail de teste sem erro de conexão', function () {
    Mail::fake();

    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaEmail::class)
        ->set('smtp_host', 'smtp.exemplo.com')
        ->set('emailTeste', 'destino@exemplo.com')
        ->call('enviarTeste')
        ->assertHasNoErrors()
        ->assertDispatched('toast', variant: 'success');
});

it('mantém a senha SMTP atual quando o campo é deixado em branco', function () {
    $atual = app(EmailSettings::class);
    $atual->smtp_password = 'segredo-antigo';
    $atual->save();

    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaEmail::class)
        ->set('smtp_host', 'smtp.novo.com')
        ->set('smtp_password', '')
        ->call('salvar')
        ->assertHasNoErrors();

    expect(app(EmailSettings::class)->smtp_password)->toBe('segredo-antigo');
});

it('salva configurações de localização', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaLocalizacao::class)
        ->set('idioma', 'en')
        ->set('timezone', 'America/Manaus')
        ->call('salvar')
        ->assertHasNoErrors();

    $settings = app(LocalizacaoSettings::class);

    expect($settings->idioma)->toBe('en')
        ->and($settings->timezone)->toBe('America/Manaus');
});

it('rejeita fuso horário inválido', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaLocalizacao::class)
        ->set('timezone', 'Foo/Bar')
        ->call('salvar')
        ->assertHasErrors(['timezone']);
});

it('salva a política de segurança', function () {
    // Salvar a aba exige step-up; sem 2FA, cai no fallback de senha (já confirmada).
    session()->put('auth.password_confirmed_at', time());

    Livewire::actingAs($this->admin, 'admin')
        ->test(AbaSeguranca::class)
        ->set('senha_min_caracteres', 12)
        ->set('senha_exige_especial', true)
        ->call('salvar')
        ->assertHasNoErrors();

    $settings = app(SegurancaSettings::class);

    expect($settings->senha_min_caracteres)->toBe(12)
        ->and($settings->senha_exige_especial)->toBeTrue();
});

it('aplica a política de senha ao cadastrar usuário', function () {
    // Política de fábrica exige maiúsculas e números.
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'fulano@teste.com')
        ->set('modoAcesso', 'manual')
        ->set('password', 'apenasminusculas')
        ->call('salvar')
        ->assertHasErrors(['password']);
});
