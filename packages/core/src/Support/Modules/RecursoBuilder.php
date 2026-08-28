<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Modules;

use Illuminate\Support\Str;

/**
 * Um recurso dentro de um módulo — e as cinco convenções que saem da sua chave.
 *
 * | Derivado          | De 'alunos' no módulo 'escola'                              |
 * |-------------------|-------------------------------------------------------------|
 * | permissões        | escola.alunos.{listar,criar,editar,deletar,restaurar,…}      |
 * | key do menu       | escola-alunos                                                |
 * | rota              | admin.escola.alunos.index                                    |
 * | permissão do item | escola.alunos.listar                                         |
 * | active            | ['admin.escola.alunos.*']                                    |
 *
 * Cada uma dessas era digitada à mão em algum lugar, e cada lugar era uma
 * chance de divergir. A do meio já divergiu: o gerador calculava a permissão de
 * listagem por `snakePlural()` enquanto o catálogo usava `permissaoBase()`, e a
 * tela do RH exigia uma permissão que não existia — o seletor de empresas
 * nascia vazio para todo mundo que não fosse super-admin, sem erro nenhum.
 */
final class RecursoBuilder
{
    private string $label;

    private string $icone = 'tabler--folder';

    private ?int $ordem = null;

    private bool $comLixeira = true;

    private ?string $grupo = null;

    private ?string $rotaBase = null;

    private ?string $singular = null;

    private bool $comMenu = true;

    public function __construct(
        private readonly ModuloBuilder $modulo,
        public readonly string $chave,
    ) {
        $this->label = Str::headline($chave);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Singular do rótulo, para as descrições de permissão.
     *
     * Não é derivado: o inflector do Laravel é inglês, e `Str::singular('paises')`
     * não devolve 'país'.
     */
    public function singular(string $singular): self
    {
        $this->singular = $singular;

        return $this;
    }

    public function icone(string $icone): self
    {
        $this->icone = $icone;

        return $this;
    }

    public function ordem(int $ordem): self
    {
        $this->ordem = $ordem;

        return $this;
    }

    /** Sem SoftDeletes: some restaurar/excluir_permanente, e deletar remove. */
    public function semLixeira(): self
    {
        $this->comLixeira = false;

        return $this;
    }

    /** Sem item na sidebar: a tela existe, mas só se chega a ela por link direto. */
    public function semMenu(): self
    {
        $this->comMenu = false;

        return $this;
    }

    public function temMenu(): bool
    {
        return $this->comMenu;
    }

    public function noGrupo(string $chave): self
    {
        $this->grupo = $chave;

        return $this;
    }

    /**
     * Prefixo da rota, quando ele não segue `admin.{modulo}.{recurso}`.
     *
     * A extensão fiscal é o caso: as rotas dela vivem sob `admin.referencia.*`
     * porque os catálogos ficam na tela de Tabelas Auxiliares.
     */
    public function rotaBase(string $prefixo): self
    {
        $this->rotaBase = $prefixo;

        return $this;
    }

    /**
     * Fecha o recurso e devolve o módulo, para encadear o próximo.
     *
     * Não empurra nada: quem coleta é `ModuloBuilder::recurso()`, pelo motivo
     * documentado lá. O que este método faz é marcar, para quem lê a cadeia,
     * onde ela volta do recurso para o módulo — sem essa marca, `->label()` no
     * meio de uma cadeia longa não diria em qual dos dois objetos está mexendo.
     */
    public function registrar(): ModuloBuilder
    {
        return $this->modulo;
    }

    public function permissaoBase(): string
    {
        return "{$this->modulo->chave}.{$this->chave}";
    }

    public function keyDeMenu(): string
    {
        return "{$this->modulo->chave}-{$this->chave}";
    }

    public function prefixoDeRota(): string
    {
        return $this->rotaBase ?? "admin.{$this->modulo->chave}.{$this->chave}";
    }

    /**
     * @return array<string, array{label: string, descricao: string}>
     */
    public function permissoes(): array
    {
        $base = $this->permissaoBase();
        $plural = mb_strtolower($this->label);
        $singular = mb_strtolower($this->singular ?? $this->label);

        $permissoes = [
            "{$base}.listar" => ['label' => "Listar {$plural}", 'descricao' => "Ver a listagem de {$plural}."],
            "{$base}.criar" => ['label' => "Criar {$plural}", 'descricao' => "Cadastrar novos registros de {$singular}."],
            "{$base}.editar" => ['label' => "Editar {$plural}", 'descricao' => "Alterar dados e status de {$plural}."],
            "{$base}.deletar" => [
                'label' => "Excluir {$plural}",
                'descricao' => $this->comLixeira ? "Mover {$plural} para a lixeira." : "Remover {$plural}.",
            ],
        ];

        if (! $this->comLixeira) {
            return $permissoes;
        }

        $permissoes["{$base}.restaurar"] = [
            'label' => "Restaurar {$plural}",
            'descricao' => "Restaurar {$plural} da lixeira.",
        ];
        $permissoes["{$base}.excluir_permanente"] = [
            'label' => "Excluir {$plural} permanentemente",
            'descricao' => "Remover {$plural} definitivamente do banco (irreversível).",
        ];

        return $permissoes;
    }

    /**
     * @return array<string, mixed>
     */
    public function itemDeMenu(): array
    {
        $prefixo = $this->prefixoDeRota();

        $item = [
            'key' => $this->keyDeMenu(),
            'label' => $this->label,
            'icon' => $this->icone,
            'route' => "{$prefixo}.index",
            'permission' => "{$this->permissaoBase()}.listar",
            'active' => ["{$prefixo}.*"],
        ];

        if ($this->ordem !== null) {
            $item['ordem'] = $this->ordem;
        }

        if ($this->grupo !== null) {
            $item['grupo'] = $this->grupo;
        }

        return $item;
    }
}
