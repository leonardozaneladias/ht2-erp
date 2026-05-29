# Password Meter (Força de Senha)

**Categoria:** Plugin
**Origem Inspinia:** `resources/views/plugins/pass-meter.blade.php`
**Plugins JS:** Nenhum (implementação Alpine custom)
**Uso típico:** Cadastro/edição de usuários — medidor visual de força de senha

---

## Descrição

Medidor visual de força de senha — barra de progresso colorida + label ("Fraca", "Média", "Forte", "Muito forte"). Calcula score baseado em: comprimento, letras maiúsculas/minúsculas, números, caracteres especiais.

---

## Abordagem: Alpine puro

O Inspinia usa biblioteca externa; vamos implementar direto em Alpine para evitar dependência.

### Score calculation

```js
function calcScore(password) {
    let score = 0;
    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 15;
    if (/[a-z]/.test(password)) score += 10;
    if (/[A-Z]/.test(password)) score += 15;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^a-zA-Z0-9]/.test(password)) score += 20;
    return Math.min(100, score);
}
```

### Faixas

- 0–25: Muito fraca (vermelho)
- 26–50: Fraca (laranja)
- 51–75: Média (amarelo)
- 76–100: Forte (verde)

---

## Implementação integrada ao `<x-shared.password-input>`

O componente `<x-shared.password-input>` já tem a prop `with-meter` (ver `password-input.md`). Aqui documentamos a lógica Alpine do medidor.

### Código (versão standalone)

```blade
{{-- resources/views/components/shared/password-strength-meter.blade.php --}}
@props (['target'])

<div
    x-data="{
        password: '',
        score: 0,
        label: '',
        barClass: '',
        calc() {
            let s = 0
            if (this.password.length >= 8) s += 25
            if (this.password.length >= 12) s += 15
            if (/[a-z]/.test(this.password)) s += 10
            if (/[A-Z]/.test(this.password)) s += 15
            if (/[0-9]/.test(this.password)) s += 15
            if (/[^a-zA-Z0-9]/.test(this.password)) s += 20
            this.score = Math.min(100, s)

            if (this.score <= 25) { this.label = 'Muito fraca'; this.barClass = 'bg-danger' }
            else if (this.score <= 50) { this.label = 'Fraca'; this.barClass = 'bg-warning' }
            else if (this.score <= 75) { this.label = 'Média'; this.barClass = 'bg-info' }
            else { this.label = 'Forte'; this.barClass = 'bg-success' }
        }
    }"
    x-init="
        $watch('password', () => calc());
        $el.previousElementSibling.querySelector('input').addEventListener('input', (e) => password = e.target.value);
    "
>
    <div class="h-1 bg-default-200 rounded-full overflow-hidden">
        <div class="h-full transition-all duration-300" :class="barClass" :style="`width: ${score}%`"></div>
    </div>
    <small
        class="text-xs mt-1 block"
        :class="{
               'text-danger': score <= 25,
               'text-warning': score > 25 && score <= 50,
               'text-info': score > 50 && score <= 75,
               'text-success': score > 75,
               'text-default-400': score === 0,
           }"
        x-text="score === 0 ? 'Digite uma senha' : label"
    ></small>
</div>
```

---

## Exemplo de Uso

### Via password-input

```blade
<x-shared.password-input
    name="password"
    label="Nova Senha"
    hint="Mín 8 caracteres com letras, números e símbolos"
    with-meter
    wire:model="form.password"
    required
/>
```

### Standalone

```blade
<x-shared.input name="senha" label="Senha" type="password" wire:model="senha" /> <x-shared.password-strength-meter />
```

---

## Validação server-side (Laravel)

A barra é só UX — a validação real é no Laravel:

```php
use Illuminate\Validation\Rules\Password;

public function rules(): array
{
    return [
        'password' => [
            'required',
            'confirmed',
            Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),  // checa haveibeenpwned.com
        ],
    ];
}
```

---

## Onde Se Aplica

| Contexto         | Uso                       |
| ---------------- | ------------------------- |
| Usuários         | Cadastro e reset de senha |
| Account Settings | Alterar senha             |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Sem biblioteca externa** — helper JS leve reaproveitado de `resources/js/admin/forms.js`
2. **Score ponderado** — comprimento pesa mais que variety
3. **Cores semânticas** — vermelho→verde mapeia intuição do usuário
4. **Não bloqueia submit** — mesmo senha "Muito fraca" pode ser aceita se passar na validação Laravel
5. **`Password::uncompromised()`** — consulta API do haveibeenpwned.com para rejeitar senhas vazadas
6. **Mensagem localizada** — "Muito fraca", "Fraca", etc. em pt-BR
7. **Debounce não necessário** — o cálculo continua instantâneo

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/shared/password-strength-meter.blade.php`
- `resources/views/components/shared/password-input.blade.php`
- `resources/js/admin/forms.js`
  **Preview:** `resources/views/admin/dev/components/password-strength-meter.blade.php`

### API final consolidada

| Prop      | Tipo      | Default | Observação                                                              |
| --------- | --------- | ------- | ----------------------------------------------------------------------- |
| `fieldId` | `?string` | `null`  | Usado para o modo standalone, apontando para um input existente         |
| `labelId` | `?string` | `null`  | Permite conectar `aria-describedby` quando embutido no `password-input` |

### Observações de implementação

- o uso preferencial continua sendo `x-shared.password-input with-meter`
- o subcomponente oficial existe para cenários standalone e para padronizar o markup interno do meter
- toda a lógica de score, cores e labels é compartilhada com o helper dos forms base
