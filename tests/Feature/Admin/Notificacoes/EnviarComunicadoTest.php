<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Notificacoes\EnviarComunicado;
use HT2ML\Core\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('gestor', 50);
});

function superAdminLogado(): HT2ML\Core\Models\AdminUser
{
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    test()->actingAs($super, 'admin');

    return $super;
}

it('envia para todos os usuários ativos e ignora os inativos', function (): void {
    $super = superAdminLogado();
    $ativo = criarAdminUser('ativo@teste.com', true);
    $inativo = criarAdminUser('inativo@teste.com', false);

    Livewire::test(EnviarComunicado::class)
        ->set('tipo', 'info')
        ->set('titulo', 'Manutenção')
        ->set('mensagem', 'Haverá manutenção no domingo.')
        ->set('publico', 'todos')
        ->call('enviar')
        ->assertHasNoErrors();

    expect($super->notifications()->count())->toBe(1)
        ->and($ativo->notifications()->count())->toBe(1)
        ->and($inativo->notifications()->count())->toBe(0);
});

it('envia apenas para usuários de um papel', function (): void {
    superAdminLogado();
    criarRoleAdmin('editor', 10);
    $editor = criarAdminUser('editor@teste.com');
    $editor->assignRole('editor');
    $outro = criarAdminUser('outro@teste.com');

    Livewire::test(EnviarComunicado::class)
        ->set('tipo', 'aviso')
        ->set('titulo', 'Só para editores')
        ->set('mensagem', 'Mensagem direcionada.')
        ->set('publico', 'papel')
        ->set('papel', 'editor')
        ->call('enviar')
        ->assertHasNoErrors();

    expect($editor->notifications()->count())->toBe(1)
        ->and($outro->notifications()->count())->toBe(0);
});

it('exige papel quando o público é por papel', function (): void {
    superAdminLogado();

    Livewire::test(EnviarComunicado::class)
        ->set('titulo', 'X')
        ->set('mensagem', 'Y')
        ->set('publico', 'papel')
        ->set('papel', '')
        ->call('enviar')
        ->assertHasErrors(['papel']);
});

it('valida título e mensagem obrigatórios', function (): void {
    superAdminLogado();

    Livewire::test(EnviarComunicado::class)
        ->set('titulo', '')
        ->set('mensagem', '')
        ->call('enviar')
        ->assertHasErrors(['titulo', 'mensagem']);
});

it('nega acesso a quem não tem a permissão de enviar', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');

    $this->actingAs($gestor, 'admin')
        ->get(route('admin.comunicados'))
        ->assertForbidden();
});

it('sanitiza o HTML da mensagem (remove script e atributos de evento)', function (): void {
    $super = superAdminLogado();

    Livewire::test(EnviarComunicado::class)
        ->set('tipo', 'info')
        ->set('titulo', 'Rich text')
        ->set('mensagem', '<p>Olá <strong>time</strong></p><script>alert(1)</script><a href="https://x.com" onclick="evil()">link</a>')
        ->set('publico', 'todos')
        ->call('enviar')
        ->assertHasNoErrors();

    $mensagem = (string) $super->notifications()->first()?->data['mensagem'];

    expect($mensagem)
        ->toContain('<strong>time</strong>')
        ->not->toContain('<script')
        ->not->toContain('onclick');
});

it('rejeita mensagem só com HTML vazio (texto em branco)', function (): void {
    superAdminLogado();

    Livewire::test(EnviarComunicado::class)
        ->set('titulo', 'X')
        ->set('mensagem', '<p><br></p>')
        ->set('publico', 'todos')
        ->call('enviar')
        ->assertHasErrors(['mensagem']);
});

it('rejeita mensagem com texto acima de 1000 caracteres', function (): void {
    superAdminLogado();

    Livewire::test(EnviarComunicado::class)
        ->set('titulo', 'X')
        ->set('mensagem', '<p>' . str_repeat('a', 1001) . '</p>')
        ->set('publico', 'todos')
        ->call('enviar')
        ->assertHasErrors(['mensagem']);
});

it('registra o envio na auditoria', function (): void {
    superAdminLogado();
    criarAdminUser('destino@teste.com');

    Livewire::test(EnviarComunicado::class)
        ->set('tipo', 'info')
        ->set('titulo', 'Auditável')
        ->set('mensagem', 'Mensagem.')
        ->set('publico', 'todos')
        ->call('enviar')
        ->assertHasNoErrors();

    expect(Activity::query()->where('event', 'comunicado_enviado')->exists())->toBeTrue();
});
