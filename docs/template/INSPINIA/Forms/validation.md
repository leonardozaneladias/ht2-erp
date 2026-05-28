# Form Validation (Padrão)

**Categoria:** Form (padrão, não componente)
**Origem Inspinia:** `resources/views/form/validation.blade.php`
**Plugins JS:** Nenhum (validação server-side via Laravel)
**Plugins CSS:** Apenas Tailwind (`border-danger!`, `text-danger`)

---

## Descrição

Não é um componente Blade — é o **padrão** de exibição de erros de validação integrado em todos os form components. Cada input (`<x-shared.input>`, `<x-shared.textarea>`, etc.) já chama `$errors->has($name)` e `$errors->first($name)` internamente.

> O Inspinia mostra validação com classes `.form-input.is-valid` / `.is-invalid` + `.valid-feedback` / `.invalid-feedback`. Nossa implementação usa `border-danger!` + `<small class="text-danger">` — mais simples e compatível com Tailwind 4.

---

## Padrão (já implementado nos inputs)

Todos os form components seguem este padrão:

```blade
@php $hasError = $errors->has($name); @endphp

<div class="mb-4">
    {{-- label --}}

    <input class="form-input {{ $hasError ? 'border-danger!' : '' }}" ... />

    @if ($hasError)
        <small class="text-danger mt-1 block text-xs"> {{ $errors->first($name) }} </small>
    @elseif ($hint)
        <small class="text-default-400 mt-1 block text-xs">{{ $hint }}</small>
    @endif
</div>
```

---

## FormRequest (camada de validação)

Conforme CLAUDE.md §7.2, **toda validação fica em FormRequest**, não no Controller nem no Livewire inline. Pattern:

```php
// app/Http/Requests/Admin/StoreInstituicaoRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use LaravelLegends\PtBrValidator\Rules\Cnpj;

final class StoreInstituicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')->can('instituicoes.criar');
    }

    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', new Cnpj, 'unique:instituicoes,cnpj'],
            'cep' => ['required', 'regex:/^\d{5}-?\d{3}$/'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'bairro' => ['required', 'string', 'max:100'],
            'cidade' => ['required', 'string', 'max:100'],
            'uf' => ['required', 'string', 'size:2'],
            'telefone' => ['nullable', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB
            'ativo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'logo.max' => 'A imagem deve ter no máximo 2MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'razao_social' => 'razão social',
            'nome_fantasia' => 'nome fantasia',
            'uf' => 'UF',
        ];
    }
}
```

---

## Validação em Livewire

Livewire usa `#[Validate]` attributes OU `$rules` property. Padrão recomendado para o ArtFinal:

```php
// app/Livewire/Admin/Instituicoes/Form.php
use App\Http\Requests\Admin\StoreInstituicaoRequest;

class Form extends Component
{
    public InstituicaoData $form;

    protected function rules(): array
    {
        return (new StoreInstituicaoRequest)->rules();
    }

    public function salvar(): void
    {
        $validated = $this->validate();
        app(CreateInstituicaoAction::class)->execute($validated);
        $this->dispatch('toast', variant: 'success', message: 'Instituição criada.');
    }
}
```

---

## Pacote de validação pt-BR

```bash
composer require laravellegends/pt-br-validator
```

Rules disponíveis:

- `Cpf` — valida CPF (ignora máscara)
- `Cnpj` — valida CNPJ
- `Celular` / `TelefoneComDdd` — valida telefone BR
- `FormatoCep` — valida formato `XXXXX-XXX`
- `Placa` — placa de veículo (não usado no ArtFinal)

Mensagens em pt-BR vêm do pacote `laravel-lang/lang` (já em `resources/lang/pt_BR/validation.php`).

---

## Checklist de validação por tela (PRD)

| Tela                 | Validações críticas                                                                 |
| -------------------- | ----------------------------------------------------------------------------------- |
| 14.3 Instituições    | CNPJ único, CEP formato, email formato, logo max 2MB                                |
| 14.4 Contratos       | Código turma único + alfanumérico maiúsculo, ano ≥ atual, data_evento > data_inicio |
| 14.6 Produtos        | Contrato válido, categoria válida, data_fim > data_inicio, imagem max 2MB           |
| 14.7 Programações    | Sem sobreposição (rule custom), valor > 0, parcelas ≥ 1                             |
| 14.8 Condições       | parcela_max ≥ parcela_min, data_limite obrigatória se híbrida                       |
| 14.9 Descontos       | parcela_ate ≥ parcela_de, percentual 0.01–100, sem sobreposição                     |
| 14.11 Termos         | Nome único, versão obrigatória                                                      |
| 14.12 Formandos      | CPF único + válido, data_nascimento ≥ 10 anos atrás                                 |
| 14.15 Configurações  | Dias entre 1-31, valor_minimo_parcela > 0                                           |
| 14.18 Usuários Admin | Email único, senha mín 8 chars + letras + números                                   |

---

## Rules customizadas que precisaremos criar

| Rule                           | Uso                                                          |
| ------------------------------ | ------------------------------------------------------------ |
| `SemSobreposicaoProgramacao`   | Valida que nova programação não colide com existentes (14.7) |
| `SemSobreposicaoDesconto`      | Idem para descontos (14.9)                                   |
| `CodigoTurmaAlfanumericoUpper` | `/^[A-Z0-9]+$/`                                              |
| `DataFuturaOuIgual`            | Data ≥ hoje                                                  |
| `AnoMinimoAtual`               | Ano ≥ now()->year                                            |

---

## Classificação

| Critério         | Valor                                  |
| ---------------- | -------------------------------------- |
| **Vai usar**     | 🟢 Sim (padrão)                        |
| **Prioridade**   | P2 (Onda 3)                            |
| **Complexidade** | Baixa (pattern já embutido nos inputs) |
| **Status**       | 🔴 Não iniciado                        |

---

## Notas de Adaptação

1. **Não é componente** — é pattern + FormRequest + Rules. Cada input Blade já renderiza o erro
2. **FormRequest obrigatório** por CLAUDE.md §7.2 — nunca validar no controller
3. **Livewire reusa FormRequest** via `(new XRequest)->rules()` — DRY
4. **pt-br-validator** para CPF/CNPJ/Telefone/CEP
5. **Mensagens em pt-BR** via `laravel-lang/lang` já instalado
6. **Rules custom** ficam em `app/Rules/*.php` — criar conforme necessário por sprint
