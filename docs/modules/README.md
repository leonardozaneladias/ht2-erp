# Módulos Backend — Índice

Documentação per-módulo do backend Laravel. Cada arquivo descreve responsabilidades, models, services e jobs de um módulo específico.

Para specs verticais completas (BE+FE+API+testes), veja [`features/`](../features/README.md).

| #   | Módulo                                                   | Fase              | SPEC relacionada                                         |
| --- | -------------------------------------------------------- | ----------------- | -------------------------------------------------------- |
| 02  | [Auth Portal](02-auth-portal.md)                         | 1 — Fundação      | [SPEC-001](../features/SPEC-001-login.md)                |
| 03  | [Contratos e Turmas](03-contratos-turmas.md)             | 1 — Fundação      | —                                                        |
| 04  | [Instituições e Cursos](04-instituicoes-cursos.md)       | 1 — Fundação      | —                                                        |
| 05  | [Produtos e Pacotes](05-produtos-pacotes.md)             | 1 — Fundação      | —                                                        |
| 06  | [Programações e Valor](06-programacoes-valor.md)         | 1 — Fundação      | —                                                        |
| 07  | [Condições de Pagamento](07-condicoes-pagamento.md)      | 1 — Fundação      | —                                                        |
| 08  | [Descontos](08-descontos.md)                             | 1 — Fundação      | —                                                        |
| 09  | [Configurações Globais](09-configuracoes-globais.md)     | 1 — Fundação      | —                                                        |
| 10  | [Cálculo de Parcelas](10-calculo-parcelas.md)            | 1 — Fundação      | [SPEC-003](../features/SPEC-003-financeiro-pagamento.md) |
| 11  | [Adesão / Wizard](11-adesao-wizard.md)                   | 2 — Portal Adesão | [SPEC-002](../features/SPEC-002-wizard-adesao.md)        |
| 12  | [Área do Formando](12-area-formando.md)                  | 3 — Portal Área   | [SPEC-009](../features/SPEC-009-perfil.md)               |
| 13  | [Formandos e Responsáveis](13-formandos-responsaveis.md) | 2 — Portal Adesão | [SPEC-002](../features/SPEC-002-wizard-adesao.md)        |
| 14  | [Parcelas e Financeiro](14-parcelas-financeiro.md)       | 2 — Portal Adesão | [SPEC-003](../features/SPEC-003-financeiro-pagamento.md) |
| 15  | [Termos de Adesão](15-termos-adesao.md)                  | 2 — Portal Adesão | [SPEC-002](../features/SPEC-002-wizard-adesao.md)        |
| 16  | [Gateway Itaú](16-gateway-itau.md)                       | 4 — Gateway Real  | [SPEC-003](../features/SPEC-003-financeiro-pagamento.md) |
| 17  | [Extras](17-extras.md)                                   | 3 — Portal Área   | [SPEC-007](../features/SPEC-007-extras.md)               |
| 18  | [E-mails Transacionais](18-emails-transacionais.md)      | 5 — E-mails       | —                                                        |
| 19  | [Relatórios](19-relatorios.md)                           | 6 — Admin Core    | —                                                        |
| 20  | [Auditoria e ACL](20-auditoria-acl.md)                   | 6 — Admin Core    | —                                                        |

---

> **Template para novos módulos:** [`00-TEMPLATE-MODULO.md`](00-TEMPLATE-MODULO.md)
