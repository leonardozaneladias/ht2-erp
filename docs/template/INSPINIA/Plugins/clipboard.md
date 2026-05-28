# Clipboard (Copy to Clipboard)

**Categoria:** Plugin
**Origem Inspinia:** `resources/views/plugins/clipboard.blade.php`
**Plugins JS:** `clipboard.js` 2.0.11 (ou `navigator.clipboard` nativo)
**Uso no ArtFinal:** Copiar CPF, código de turma, link de adesão, ID de formando

---

## Descrição

Permite copiar valores para a área de transferência do usuário. Podemos usar a **API nativa `navigator.clipboard`** (HTTPS only) em vez do clipboard.js — mais simples, sem dependência. Clipboard.js fica como fallback.

---

## Abordagem ArtFinal: helper Alpine reusável

Em vez de depender da biblioteca, criar um helper Alpine que usa `navigator.clipboard.writeText()` + feedback visual.

### `<x-shared.copy-button>`

**Arquivo:** `resources/views/components/shared/copy-button.blade.php`

### Props

| Prop    | Tipo      | Default | Descrição                             |
| ------- | --------- | ------- | ------------------------------------- |
| `value` | `string`  | —       | Valor a copiar                        |
| `label` | `?string` | `null`  | Texto do botão (se omitido, só ícone) |

### Código

```blade
{{-- resources/views/components/shared/copy-button.blade.php --}}
@props ([
    'value',
    'label' => null,
])

<button
    type="button"
    x-data="{
            copied: false,
            async copy() {
                try {
                    await navigator.clipboard.writeText('{{ addslashes($value) }}')
                    this.copied = true
                    setTimeout(() => this.copied = false, 1500)
                } catch (e) {
                    this.$dispatch('toast', { variant: 'danger', message: 'Erro ao copiar' })
                }
            }
        }"
    @click="copy"
    {{ $attributes->class(['btn btn-icon btn-sm']) }}
>
    <i class="iconify" :class="copied ? 'tabler--check text-success' : 'tabler--copy'"></i>
    @if ($label)
        <span x-text="copied ? 'Copiado!' : '{{ $label }}'"></span>
    @endif
</button>
```

---

## Exemplos de Uso

### CPF copiável na ficha do formando 14.12

```blade
<div class="flex items-center gap-2">
    <span class="font-mono">{{ $formando->cpf_formatado }}</span>
    <x-shared.copy-button :value="$formando->cpf_formatado" />
</div>
```

### Código da turma

```blade
<div class="flex items-center gap-2">
    <code class="bg-default-100 px-2 py-1 rounded">{{ $contrato->codigo_turma }}</code>
    <x-shared.copy-button :value="$contrato->codigo_turma" label="Copiar" />
</div>
```

### Link de adesão pública

```blade
<div class="flex items-center gap-2 p-3 border rounded">
    <input
        type="text"
        value="{{ route('portal.adesao', $contrato->slug) }}"
        class="form-input form-input-sm grow bg-default-50"
        readonly
    />
    <x-shared.copy-button :value="route('portal.adesao', $contrato->slug)" label="Copiar Link" />
</div>
```

---

## Mapeamento no PRD

| Tela                 | Uso                                    |
| -------------------- | -------------------------------------- |
| 14.4 Contratos       | Copiar código da turma, link de adesão |
| 14.12 Ficha Formando | Copiar CPF, e-mail                     |
| 14.13 Parcelas       | Copiar ID da parcela, código de boleto |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P5 (Onda 6)  |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **`navigator.clipboard` nativo** — disponível em todos os browsers modernos (precisa HTTPS em produção)
2. **Clipboard.js fica no parking lot** — fallback para contextos não-HTTPS
3. **Feedback visual:** ícone muda para check por 1.5s, label muda para "Copiado!"
4. **Tratamento de erro:** toast se falhar (permissão negada, contexto inseguro)
5. **Helper JS leve** em vez de Alpine inline — sem dependência externa, reutilizável

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/shared/copy-button.blade.php`
- `resources/js/admin/copy.js`
  **Preview:** `resources/views/admin/dev/components/copy-button.blade.php`

### API final consolidada

| Prop             | Tipo      | Default                      |
| ---------------- | --------- | ---------------------------- |
| `value`          | `string`  | —                            |
| `label`          | `?string` | `null`                       |
| `copiedLabel`    | `string`  | `Copiado!`                   |
| `icon`           | `string`  | `tabler--copy`               |
| `successIcon`    | `string`  | `tabler--check`              |
| `successMessage` | `string`  | `Valor copiado com sucesso.` |
| `errorMessage`   | `string`  | `Erro ao copiar.`            |
| `variant`        | `string`  | `default`                    |
| `appearance`     | `string`  | `outline`                    |
| `size`           | `string`  | `sm`                         |

### Observações de implementação

- o componente final usa `navigator.clipboard` com dataset JSON e helper JS do admin
- o preview cobre casos com ícone puro, label e uso real para CPF/e-mail/código
