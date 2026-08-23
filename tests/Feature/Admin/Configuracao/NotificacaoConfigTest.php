<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuracao\AbaNotificacoes;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Services\Admin\Settings\NotificacaoService;
use HT2ML\Core\Settings\NotificacaoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('salva as preferências de notificação', function () {
    $admin = criarAdminUser('notif@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaNotificacoes::class)
        ->set('toast_posicao', 'bottom-right')
        ->set('toast_duracao', 'longa')
        ->set('toast_estilo', 'card')
        ->set('toast_maximo', 5)
        ->set('confirmacao_posicao', 'top')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('toast', variant: 'success');

    $settings = app(NotificacaoSettings::class);

    expect($settings->toast_posicao)->toBe('bottom-right')
        ->and($settings->toast_duracao)->toBe('longa')
        ->and($settings->toast_estilo)->toBe('card')
        ->and($settings->toast_maximo)->toBe(5)
        ->and($settings->confirmacao_posicao)->toBe('top');
});

it('rejeita valores inválidos', function () {
    $admin = criarAdminUser('notif@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaNotificacoes::class)
        ->set('toast_posicao', 'meio-da-tela')
        ->set('toast_maximo', 99)
        ->call('salvar')
        ->assertHasErrors(['toast_posicao', 'toast_maximo']);
});

it('expõe a config validada para o frontend (paraJsConfig)', function () {
    $settings = app(NotificacaoSettings::class);
    $settings->toast_posicao = 'bottom-center';
    $settings->toast_duracao = 'curta';
    $settings->toast_estilo = 'card';
    $settings->toast_maximo = 2;
    $settings->confirmacao_posicao = 'top';
    $settings->save();

    $config = app(NotificacaoService::class)->paraJsConfig();

    expect($config['toast']['position'])->toBe('bottom-center')
        ->and($config['toast']['duration'])->toBe(3000) // curta = 3000ms
        ->and($config['toast']['style'])->toBe('card')
        ->and($config['toast']['max'])->toBe(2)
        ->and($config['confirm']['position'])->toBe('top');
});

it('cai nos padrões quando o banco tem valor inválido', function () {
    $settings = app(NotificacaoSettings::class);
    $settings->toast_posicao = 'invalido';
    $settings->toast_maximo = 999;
    $settings->save();

    $service = app(NotificacaoService::class);

    expect($service->posicaoToast()->value)->toBe('top-center')
        ->and($service->maximoToast())->toBe(5); // clamp 1–5
});
