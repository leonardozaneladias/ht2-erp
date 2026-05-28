# Prefixos Padronizados de Cache — Portal ArtFinal

**Referência:** `docs/02-CONVENTIONS.md §9`
**Store:** Redis (`CACHE_STORE=redis` em todos os ambientes)

---

## Padrão

Todo uso de `Cache::remember()`, `Cache::put()`, `Cache::get()`, `Cache::forget()` deve seguir o prefixo adequado abaixo. Isso permite debugar o que está no Redis (`redis-cli KEYS "config:*"`), fazer invalidação em massa quando necessário, e evita colisões de chaves entre módulos.

---

## Tabela de prefixos

| Prefixo        | Uso                                                          | TTL típico | Quando invalidar                        |
| -------------- | ------------------------------------------------------------ | ---------- | --------------------------------------- |
| `config:`      | Configurações globais, dias de vencimento, categorias padrão | 24h        | Ao salvar em `ConfiguracaoController`   |
| `acl:`         | Permissões e roles de cada admin user                        | 1h         | Ao editar perfil ou permissões          |
| `programacao:` | Programação de valor vigente por produto                     | 1h         | Ao criar/editar programação             |
| `dashboard:`   | KPIs e contagens do dashboard admin                          | 5-15min    | Auto-expira (não invalidar manual)      |
| `contrato:`    | Dados enriquecidos de contrato (snapshots calculados)        | 1h         | Ao editar contrato, produto ou condição |

---

## Exemplos

```php
// ✅ CORRETO
$config = Cache::remember('config:global', 86400, fn () =>
    ConfiguracaoGlobal::all()->pluck('valor', 'chave')
);

$perms = Cache::remember("acl:user:{$userId}", 3600, fn () =>
    $user->getAllPermissions()->pluck('name')->toArray()
);

Cache::forget('config:global'); // ao salvar

// ❌ ERRADO
Cache::remember('global_config', 86400, fn () => ...); // sem prefixo
Cache::forever('dashboard:kpis', fn () => ...);         // sem TTL
```

---

## Regras

1. **SEMPRE** usar TTL explícito. `Cache::forever()` é proibido (§9.3).
2. **NUNCA** cachear dados que mudam via webhooks (parcelas, pagamentos) — §9.2.
3. **NUNCA** cachear drafts de adesão ou dados monetários em checkout — §9.2.
4. Invalidar com `Cache::forget()` sempre que o dado-fonte for alterado.
5. Chaves com ID de entidade usam dois-pontos como separador: `contrato:{id}`, `acl:user:{id}`.

---

## Debug no Redis (dev)

```bash
# Listar todas as chaves de cache do projeto
make bash
redis-cli -h redis KEYS "laravel_database_*"

# Chaves por prefixo
redis-cli -h redis KEYS "laravel_database_config:*"

# Valor de uma chave
redis-cli -h redis GET "laravel_database_config:global"
```

**Nota:** o prefixo `laravel_database_` vem do `CACHE_PREFIX` do Laravel (default). Ajustar se mudar via `.env`.
