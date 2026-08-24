<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\ExemploDemo\Livewire\Exemplos\FormExemplo;
use HT2ML\ExemploDemo\Livewire\Exemplos\IndexExemplo;
use HT2ML\ExemploDemo\Models\Exemplo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $empresa = HT2ML\Core\Models\Empresa::factory()->create();
    app(HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);
});

it('renderiza a listagem de Exemplos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexExemplo::class)
        ->assertOk();
});

it('cria um registro de Exemplo pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormExemplo::class)
        ->set('nome', 'Exemplo de Nome')
        ->set('slug', 'Exemplo de Slug')
        ->set('site', 'https://exemplo.com')
        ->set('descricao', 'Descrição de exemplo gerada pelo teste.')
        ->set('email', 'contato@exemplo.com')
        ->set('telefone', '(11) 99999-9999')
        ->set('cep', '01001-000')
        ->set('cnpj', '11.222.333/0001-81')
        ->set('cpf', '529.982.247-25')
        ->set('preco', 1990)
        ->set('custo', '10.50')
        ->set('quantidade', 1)
        ->set('cor', '#3b82f6')
        ->set('categoria', 'servico')
        ->set('tags', ['vip'])
        ->set('destaque', true)
        ->set('data_inicio', '2026-01-15')
        ->set('publicado_em', '2026-01-15')
        ->set('status', 'rascunho')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.exemplos.index'));

    expect(Exemplo::query()->count())->toBe(1);
});

it('abre o formulário de edição de Exemplo', function () {
    $registro = Exemplo::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormExemplo::class, ['exemplo' => $registro->id])
        ->assertOk()
        ->assertSet('exemploId', $registro->id);
});

// O formulário deve envolver os campos em <form wire:submit="salvar"> para que o
// Enter dentro de um campo salve o registro (PEND-01). O Exemplo é a referência viva
// copiada para novos módulos (CLAUDE.md §16), logo demonstra o padrão correto.
it('envolve os campos em <form wire:submit="salvar"> para salvar com Enter', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormExemplo::class)
        ->assertSeeHtml('wire:submit="salvar"')
        ->assertSeeHtml('type="submit"');
});

// O módulo Exemplo é a "referência viva" copiada para novos módulos (CLAUDE.md §16);
// seus rótulos devem ser exemplares em PT-BR (acentuação correta, terminologia consistente).
it('exibe os rótulos de campo corretamente acentuados em PT-BR', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormExemplo::class)
        ->assertSee('Descrição')
        ->assertSee('E-mail')
        ->assertSee('Preço (centavos)')
        ->assertSee('Serviço')
        ->assertSee('Data início')
        ->assertDontSee('Descricao')
        ->assertDontSee('Preco (centavos)')
        ->assertDontSee('Data inicio');
});
