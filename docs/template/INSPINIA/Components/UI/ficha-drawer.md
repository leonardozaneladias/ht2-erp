# ficha-drawer — drawer da ficha de visualização ("Ver")

Composição sobre `x-admin.drawer` (`size="wide"` + `blur`) que padroniza a ficha
read-only dos CRUDs: cabeçalho com o título do registro, badge "Na lixeira"
quando aplicável, corpo em slot (o partial `_ficha.blade.php` do módulo) e
rodapé com meta de criação/atualização + botões Fechar e Editar (gated por
`can('update')`, oculto para registro na lixeira). Consome o trait
`App\Livewire\Concerns\ComFicha` no Index — fluxo completo em
[`docs/visualizacao.md`](../../../visualizacao.md).

## API

```blade
<x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
    @if ($this->ficha)
        @include ('livewire.admin.exemplos._ficha', ['registro' => $this->ficha])
    @endif
</x-admin.ficha-drawer>
```

| Prop         | Tipo    | Default        | Descrição                                                               |
| ------------ | ------- | -------------- | ----------------------------------------------------------------------- |
| `registro`   | ?Model  | `null`         | Registro exibido (null = estado vazio).                                 |
| `titulo`     | ?string | `null`         | Título do drawer (fallback: "Detalhes do registro").                    |
| `editar-url` | ?string | `null`         | URL do botão Editar (só aparece com `can('update')` e fora da lixeira). |
| `id`         | string  | `drawer-ficha` | Id do overlay — 1 por página (o Index é full-page).                     |

Abre pelo evento browser `ficha-abrir` (disparado pelo `ComFicha::abrirFicha()`
após autorizar e carregar). Referência viva: módulo **Exemplo**
(`/admin/exemplos` → kebab → Ver).

Preview: `/admin/dev/components/ficha-drawer`.
