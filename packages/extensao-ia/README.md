# ht2ml/extensao-ia

Contratos de IA para produtos HT2ML (tradução, resumo e assistente), com fakes ligados por padrão, cache de tradução por hash e limites de uso por custo.

```bash
composer require ht2ml/extensao-ia
php artisan migrate   # ia_traducoes_cache
```

O pacote não escolhe provedor. Os fakes deixam o produto funcionar de ponta a ponta sem credencial. Para ligar um provedor real, troque o binding no `AppServiceProvider` do produto:

```php
$this->app->bind(TradutorInterface::class, AnthropicTradutor::class);
```

## Contratos

| Interface | Método | Fake |
|---|---|---|
| `TradutorInterface` | `traduzir($texto, $origem, $destino): array{texto}` | `[pt→en] texto` |
| `ResumidorInterface` | `resumir($titulo, $corpo, $idioma): array{texto}` | `[resumo:pt] título — início do corpo` |
| `AssistenteInterface` | `responder($pergunta, ?array $contexto, $idioma): array{resposta}` | `[assistente/pt sobre chave] pergunta` |

Todos têm `identificador(): string`, o nome do provedor que atendeu a chamada. Grave esse nome na coluna de proveniência ("qual modelo escreveu isto?"). Não use um default de migration: ele diria `fake` em silêncio no dia em que um provedor real fosse ligado.

O contexto do assistente é um mapa livre, montado pelo produto (o aluno em foco, o pedido aberto). O que é relevante é de domínio, e o contrato não sabe.

## Tradução com cache

```php
app(TraducaoService::class)->traduzir($texto, 'en', empresaId: $empresa->id);
// ['texto' => '...', 'cache' => true|false]
```

O mesmo texto (por hash) não é traduzido duas vezes para o mesmo idioma, nem entre empresas. A tabela é cache e pode ser apagada sem perda.

## Utilitários

- `TextoParaIa::limitar($texto)` corta o que é **enviado** ao provedor no teto de `config('ia.limite_caracteres')` (20.000). Ele não limita o que é gravado.
- `IdiomaDaRequisicao::codigo()` converte o locale efetivo (`pt_BR`) no código que o provedor espera (`pt`). Na `ht2ml/extensao-api`, quem resolve o locale é o `DefinirIdioma`.

## Limites por custo

`throttle:ia` (10/min) e `throttle:traducao` (20/min), por tipo + id do usuário, ou por IP. São dois, e não um, porque a tradução tem cache (repetir é grátis), enquanto o resumo e o assistente geram de novo a cada pedido. Os valores ficam em `config/ia.php` (`vendor:publish --tag=ia-config`).
