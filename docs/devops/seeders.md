# Seeders de Desenvolvimento

> Guia de referência para o ambiente de dev — quais dados existem após `php artisan migrate:fresh --seed`.

---

## Executar

```bash
make fresh
# equivale a:
#   php artisan migrate:fresh --seed         (roles, admin user)
#   php artisan db:seed --class=DevelopmentSeeder  (dados de domínio)
```

O `DevelopmentSeeder` roda em processo separado para evitar esgotamento de
memória do container — Spatie Permission + bcrypt do AdminUserSeeder
consomem RAM suficiente para matar o processo se tudo rodar junto.

Apenas em `APP_ENV=local` ou `APP_ENV=testing`.

---

## Estrutura do Seed

| Seeder                    | Qtd | Notas                               |
| ------------------------- | --- | ----------------------------------- |
| `OrganizacaoSeeder`       | 2   | ArtFinal SP, ArtFinal RJ            |
| `InstituicaoSeeder`       | 4   | 2 por organização                   |
| `CursoSeeder`             | 8   | Modalidades variadas                |
| `ContratoSeeder`          | 3   | Ativo, Rascunho, Encerrado          |
| `TurmaSeeder`             | 6   | 4 Ativas, 1 Arquivada, 1 Concluída  |
| `PacoteSeeder`            | 4   | Básico, Plus, Premium, Custom       |
| `CondicaoPagamentoSeeder` | 3   | Por contrato ativo                  |
| `ProgramacaoSeeder`       | 5   | 4 vigentes + 1 futura (Premium)     |
| `PortalUserSeeder`        | 20  | CPFs válidos, senhas padronizadas   |
| `AdesaoSeeder`            | 15  | Todos os 6 estados do StatusAdesao  |
| `ParcelaSeeder`           | ~48 | Todos os 5 estados de StatusParcela |

---

## Códigos de Turma Estáveis para QA

| Código      | Status    | Cenário                                                        |
| ----------- | --------- | -------------------------------------------------------------- |
| `FORM2027A` | Ativa     | **Caminho feliz** — wizard autenticado, login, adesão completa |
| `FORM2027B` | Ativa     | Turma com condições de pagamento alternativas                  |
| `FORM2027X` | Concluída | Deve retornar **404** no wizard público (turma encerrada)      |

---

## Usuários de Teste (PortalUser)

Todos têm senha: `Senha@123`

| E-mail                                                 | Cenário       | Adesão                                  |
| ------------------------------------------------------ | ------------- | --------------------------------------- |
| `feliz@teste.com`                                      | Caminho feliz | Ativa — Pacote Básico, formando 7       |
| `inadimplente@teste.com`                               | Inadimplência | Inadimplente — Pacote Plus, formando 13 |
| `portal.user1@teste.com` até `portal.user20@teste.com` | QA geral      | Sem adesão                              |

---

## Adesões por Status

| Status               | Qtd | Formandos | Observações                                    |
| -------------------- | --- | --------- | ---------------------------------------------- |
| `rascunho`           | 3   | 1–3       | Sem portal_user, Pacote Básico                 |
| `pendente_pagamento` | 3   | 4–6       | Aceito há 2 dias, Pacote Plus                  |
| `ativa`              | 4   | 7–10      | Pacotes variados; formando 7 → feliz@teste.com |
| `cancelada`          | 2   | 11–12     | Motivo de cancelamento preenchido              |
| `inadimplente`       | 2   | 13–14     | formando 13 → inadimplente@teste.com           |
| `concluida`          | 1   | 15        | Histórico, Pacote Premium                      |

---

## Parcelas por Status (por tipo de adesão)

| Adesão            | Parcelas | Distribuição                          |
| ----------------- | -------- | ------------------------------------- |
| Ativa (×4)        | 6 cada   | 2 `paga` + 4 `pendente`               |
| Inadimplente (×2) | 6 cada   | 2 `paga` + 2 `vencida` + 2 `pendente` |
| Concluída (×1)    | 12       | 11 `paga` + 1 `estornada` (última)    |

> `cancelada` não tem parcelas — adesão foi cancelada antes da cobrança.

---

## Determinismo

O `DevelopmentSeeder` inicia com `FakerFactory::create()->seed(42)`, garantindo que duas execuções limpas (`migrate:fresh --seed`) produzem os mesmos dados. Útil para reproduzir bugs apontados com screenshot ou ID específico.

---

## Idempotência

Todos os seeders usam `firstOrCreate` com chave estável (`correlation_id`, `codigo`, `email` etc.). Executar um seeder isolado duas vezes é seguro.

---

## Adicionar Novos Seeders

Qualquer migration futura que crie tabela de domínio deve incluir factory + seeder no mesmo commit. Registrar o seeder em `DevelopmentSeeder::run()` na posição correta da ordem topológica.
