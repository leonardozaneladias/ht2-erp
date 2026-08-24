<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Livewire\Exemplos;

use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\ExemploDemo\Actions\CreateExemploAction;
use HT2ML\ExemploDemo\Actions\UpdateExemploAction;
use HT2ML\ExemploDemo\DTOs\ExemploDTO;
use HT2ML\ExemploDemo\Http\Requests\ExemploRules;
use HT2ML\ExemploDemo\Models\Exemplo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormExemplo extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $exemploId = null;

    public ?string $filial_id = null;

    public string $nome = '';

    public string $slug = '';

    public ?string $site = null;

    public ?string $descricao = null;

    public string $email = '';

    public ?string $telefone = null;

    public ?string $cep = null;

    public ?string $cnpj = null;

    public ?string $cpf = null;

    public int $preco = 0;

    public string $custo = '';

    public int $quantidade = 0;

    public ?string $cor = null;

    public string $categoria = '';

    /** @var list<string> */
    public array $tags = [];

    public bool $destaque = false;

    public string $data_inicio = '';

    public ?string $publicado_em = null;

    public string $status = 'rascunho';

    public function mount(?int $exemplo = null): void
    {
        if ($exemplo !== null) {
            $registro = Exemplo::findOrFail($exemplo);
            $this->authorize('update', $registro);

            $this->exemploId = $registro->id;
            $this->filial_id = $registro->filial_id === null ? null : (string) $registro->filial_id;
            $this->nome = (string) $registro->nome;
            $this->slug = (string) $registro->slug;
            $this->site = $registro->site;
            $this->descricao = $registro->descricao;
            $this->email = (string) $registro->email;
            $this->telefone = $registro->telefone;
            $this->cep = $registro->cep;
            $this->cnpj = $registro->cnpj;
            $this->cpf = $registro->cpf;
            $this->preco = (int) $registro->preco;
            $this->custo = (string) $registro->custo;
            $this->quantidade = (int) $registro->quantidade;
            $this->cor = $registro->cor;
            $this->categoria = (string) $registro->categoria;
            $this->tags = (array) $registro->tags;
            $this->destaque = (bool) $registro->destaque;
            $this->data_inicio = $registro->data_inicio->format('Y-m-d');
            $this->publicado_em = $registro->publicado_em?->format('Y-m-d H:i');
            $this->status = $registro->status->value;

            return;
        }

        $this->authorize('create', Exemplo::class);
    }

    public function salvar(CreateExemploAction $criar, UpdateExemploAction $atualizar): void
    {
        $dados = $this->validate(
            ExemploRules::regras($this->exemploId),
            attributes: $this->validationAttributes(),
        );

        $dto = ExemploDTO::fromArray($dados);

        if ($this->exemploId === null) {
            $criar->execute($dto);
            $this->notificarAposRedirect('success', 'Exemplo criado(a) com sucesso.');
        } else {
            $atualizar->execute(Exemplo::findOrFail($this->exemploId), $dto);
            $this->notificarAposRedirect('success', 'Exemplo atualizado(a) com sucesso.');
        }

        $this->redirect(route('admin.exemplos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('exemplo-demo::exemplos.form-exemplo', [
            'modo' => $this->exemploId === null ? 'criar' : 'editar',
            'filiais' => $this->filiaisDaEmpresaAtiva(),
        ])->title($this->exemploId === null ? 'Novo registro' : 'Editar registro');
    }

    /**
     * Filiais ativas da empresa ativa (opções do select de filial). Vazio quando
     * não há empresa ativa no contexto.
     *
     * @return array<int, string>
     */
    protected function filiaisDaEmpresaAtiva(): array
    {
        $empresaId = app(\HT2ML\Core\Support\Tenancy\TenantContext::class)->empresaAtivaId();

        if ($empresaId === null) {
            return [];
        }

        return \HT2ML\Core\Models\Filial::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'filial_id' => 'filial',
            'nome' => 'nome',
            'slug' => 'slug',
            'site' => 'site',
            'descricao' => 'descricao',
            'email' => 'email',
            'telefone' => 'telefone',
            'cep' => 'cep',
            'cnpj' => 'cnpj',
            'cpf' => 'cpf',
            'preco' => 'preco',
            'custo' => 'custo',
            'quantidade' => 'quantidade',
            'cor' => 'cor',
            'categoria' => 'categoria',
            'tags' => 'tags',
            'destaque' => 'destaque',
            'data_inicio' => 'data inicio',
            'publicado_em' => 'publicado em',
            'status' => 'status',
        ];
    }
}
