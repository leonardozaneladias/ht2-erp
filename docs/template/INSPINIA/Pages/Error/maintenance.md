# Maintenance Mode (503)

**Categoria:** Page / Error
**Origem Inspinia:** `resources/views/error/maintenance.blade.php`

---

## Descrição

Página exibida quando o Laravel está em **maintenance mode** (`php artisan down`). Padrão Laravel usa `resources/views/errors/503.blade.php` automaticamente.

---

## View Proposta

**Arquivo:** `resources/views/errors/503.blade.php`

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manutenção | Portal ArtFinal</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <x-admin.partials.theme-bootstrap />
    @vite (['resources/css/admin.css'])
</head>
<body>
    <div class="flex min-h-screen items-center">
        <div class="container">
            <div class="flex justify-center p-12">
                <div class="md:w-1/2 text-center">
                    <div
                        class="bg-warning/10 text-warning size-28 rounded-full flex items-center justify-center mx-auto mb-6"
                    >
                        <i class="iconify tabler--tool text-6xl"></i>
                    </div>
                    <h3 class="mb-2 text-2xl font-bold uppercase">Sistema em Manutenção</h3>
                    <p class="text-default-400 mb-6">Estamos realizando uma atualização programada. Voltaremos em breve.</p>

                    @isset ($message)
                        <x-shared.alert variant="info" class="max-w-md mx-auto mb-6"> {{ $message }} </x-shared.alert>
                    @endisset

                    @isset ($retryAfter)
                        <p class="text-default-400 text-sm">Tente novamente em aproximadamente {{ ceil($retryAfter / 60) }} minutos.</p>
                    @endisset

                    <p class="text-default-400 text-xs mt-6">
                        Urgente? Contate
                        <a href="mailto:suporte@artfinal.com.br" class="text-primary">suporte@artfinal.com.br</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## Uso via artisan

```bash
# Ativar maintenance mode com mensagem e retry
php artisan down --message="Atualização do sistema" --retry=600

# Excluir IPs específicos (equipe durante deploy)
php artisan down --secret="deploy-token-xxx"

# Desativar
php artisan up
```

---

## Classificação

| Critério         | Valor           |
| ---------------- | --------------- |
| **Vai usar**     | 🟢 Sim          |
| **Prioridade**   | P4              |
| **Complexidade** | Trivial         |
| **Status**       | 🔴 Não iniciado |

---

## Notas de Adaptação

1. **Laravel passa `$message` e `$retryAfter`** automaticamente quando uso `--message=` e `--retry=`
2. **Ícone `tabler--tool`** em vez de SVG externo
3. **Cor warning** (amarelo) — transmite "aviso, não erro"
4. **`--secret`** permite equipe acessar durante maintenance via `?secret=xxx`
5. **Sem Livewire** — Livewire precisa conectar ao servidor, não funciona em maintenance
