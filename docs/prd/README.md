# PRD & Planejamento Técnico

Fontes primárias do projeto — documentos de referência para decisões de produto, arquitetura e implementação.

| Documento                                                     | Escopo                                                                     | Audiência               |
| ------------------------------------------------------------- | -------------------------------------------------------------------------- | ----------------------- |
| [PRD v4](PRD_v4.md)                                           | Requisitos completos: 31 tabelas, 20+ telas, 26 sprints, regras de negócio | Todos                   |
| [Planejamento Backend API v1](PLANEJAMENTO_BACKEND_APIV1.md)  | Plano executável do backend: contratos REST, migrations, services, jobs    | Dev Backend             |
| [Planejamento Frontend React](PLANEJAMENTO_FRONTEND_REACT.md) | Plano executável do frontend SPA: componentes, stores, rotas, integração   | Dev Frontend            |
| [Arquitetura Detalhada](ARQUITETURA_DETALHADA.md)             | Detalhamento da arquitetura modular Laravel                                | Arquitetos, Dev Backend |
| [Regras de Negócio](REGRAS_NEGOCIO.md)                        | Regras de domínio: formandos, parcelas, cotas, seating                     | Todos                   |
| [Performance](PERFORMANCE.md)                                 | Metas de NFR, estratégia de cache, índices, filas                          | Dev Backend, DevOps     |
| [Segurança](SEGURANCA.md)                                     | Modelo de ameaças, autenticação, autorização, LGPD                         | Dev Backend, DevOps     |
| [Roadmap](ROADMAP.md)                                         | 26 sprints, fases, dependências entre módulos                              | Produto, DevOps         |

---

> **Como usar:** Leia o PRD v4 para entender o escopo completo. Use os planejamentos como guia de execução sprint a sprint. Consulte os documentos temáticos (Regras, Segurança, Performance) ao implementar funcionalidades específicas.
>
> Para specs verticais BE+FE prontas para implementação, veja [`features/`](../features/README.md).
