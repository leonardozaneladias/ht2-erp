# PRD — Sistema de Gerenciamento de Formaturas

**Versão:** 3.1.0  
**Data:** 09/04/2026  
**Cliente:** [Nome do Cliente]  
**Responsável Técnico:** Leonardo — HT2ML TECH LTDA  
**Documento de Referência:** PRD v3.0.0 (17/03/2026)

---

## Sumário

1. Visão Geral do Produto
2. Arquitetura de Alto Nível
3. Módulos do Sistema
4. Gestão de Contratos e Turmas
5. Cadastro de Pacotes e Produtos Extras
6. Programações de Valor e Parcelamento
7. Sistema de Descontos e Condições de Pagamento
8. Configurações Globais do Sistema
9. Cálculo Dinâmico de Parcelas e Vencimentos
10. Termos de Adesão
11. Autenticação, Multi-Formando e Controle de Acesso
12. Fluxo de Adesão do Formando (Portal)
13. Área do Formando (Portal)
14. Backoffice Administrativo — Detalhamento Completo de Telas
15. Sistema de Pagamentos e Integração
16. E-mails Transacionais e Notificações
17. Modelo de Dados Completo
18. Requisitos Não-Funcionais
19. Tecnologias e Stack
20. Cronograma — Sprints de 7 Dias
21. Funcionalidades Futuras (Backlog)
22. Glossário
23. Controle de Versões

---

## 1. Visão Geral do Produto

### 1.1 Objetivo

Sistema web para gerenciamento completo de formaturas, contemplando desde o cadastro de contratos e turmas até a adesão dos formandos, gestão de pacotes/produtos, controle financeiro e acompanhamento de pagamentos. O sistema é dividido em dois ambientes completamente independentes: o Backoffice Administrativo (para a empresa organizadora) e o Portal do Formando (para alunos e responsáveis).

### 1.2 Foco da Fase 1

A fase inicial concentra-se em:

- Portal de adesão com wizard de 7 etapas e área do formando autenticada **(prioridade máxima — desenvolvido primeiro)**
- Integração com gateway Itaú (Boleto, Cartão de Crédito, PIX) **(logo após o portal funcional)**
- Sistema de e-mails transacionais e notificações
- Backoffice administrativo completo com ACL, dashboard, relatórios e gestão financeira **(após portal e gateway)**
- Cadastro e gestão de Contratos/Turmas com suporte a múltiplos cursos e períodos
- Cadastro e gestão de Formandos (com responsáveis de cadastro e financeiro)
- Cadastro e gestão de Pacotes e Produtos Extras (com categorias e grupos exclusivos)
- Programações de valor e parcelamento com cronograma automático por período
- Configuração de condições de pagamento, descontos escalonados e modalidade híbrida
- Configurações globais (vencimentos de boleto, margens de emissão, valor mínimo de parcela)
- Cálculo dinâmico de parcelas com redução automática por mês transcorrido
- Controle financeiro completo (parcelas, inadimplência, reajustes, baixas manuais)

### 1.3 Estratégia de Desenvolvimento — Portal-First

A estratégia de desenvolvimento segue a abordagem **Portal-First**: o Portal do Formando (adesão + área autenticada) é construído primeiro, seguido pela integração com o gateway de pagamentos e pelos e-mails transacionais. Somente após o portal estar funcional e validado, o Backoffice Administrativo é desenvolvido.

**Justificativa:**

- O portal é o produto voltado ao público final (formandos e responsáveis) e tem impacto direto na captação de adesões.
- Quanto antes o portal estiver no ar, antes os formandos podem começar a aderir.
- Durante o desenvolvimento do portal, os dados de teste (contratos, pacotes, programações) são alimentados via seeders e tinker, dispensando temporariamente as telas do admin.
- Ao iniciar o admin, o portal já estará estável e servindo como referência visual e funcional para o backoffice.

**Dependência resolvida:** O portal depende de dados no banco (contratos, produtos, programações, condições, termos, configurações globais). Esses dados são criados na fase de fundação via `seeders` robustos que simulam cenários reais completos, permitindo o desenvolvimento e teste do portal sem a existência do admin.

### 1.3 Stakeholders

- **Empresa organizadora de formaturas** — utiliza o backoffice para gerenciar contratos, pacotes, formandos, financeiro e configurações do sistema
- **Formandos** — acessam o portal para aderir a pacotes, acompanhar pagamentos, comprar extras e visualizar termos
- **Responsáveis financeiros** — podem ser o titular das cobranças quando o formando é menor de idade ou quando o contrato exige
- **Responsáveis por cadastro** — pessoa responsável pelo formando (geralmente quando menor de idade)

---

## 2. Arquitetura de Alto Nível

O sistema é dividido em duas grandes áreas com autenticação e base de usuários **completamente independentes**:

### 2.1 Portal do Formando

- Layout responsivo, mobile-first, otimizado para smartphones e tablets
- Acesso público para adesão via código da turma
- Acesso autenticado para a área do formando
- Login feito através da entidade `portal_users`, separada da tabela de dados do formando
- Suporte a multi-formando (um login gerenciando múltiplos formandos)
- **Template/Design:** Preline UI (Tailwind CSS) ou Tailwind puro com componentes custom — decisão a ser definida antes da Sprint 4 (ver Seção 19)

### 2.2 Backoffice Administrativo

- Layout desktop-first (mínimo 1366×768px, recomendado 1920×1080px)
- Acesso exclusivo para equipe administrativa da empresa organizadora
- Sistema de ACL com perfis e permissões granulares
- **Template:** Inspinia Multipurpose Admin Dashboard + Tailwind CSS 4, integrado com Laravel 13
- Sidebar colapsável, breadcrumbs, tema claro/escuro, componentes Inspinia nativos

---

## 3. Módulos do Sistema

### 3.1 Portal do Formando

- Tela pública de adesão (acesso via código da turma)
- Fluxo de adesão com wizard de 7 etapas: código → curso/período → pacotes → cadastro → pagamento → conferência → checkout
- Visualização de termos de cada pacote na tela de listagem com consolidação em PDF
- Área autenticada do formando com extrato financeiro, detalhes de pacotes, dados cadastrais
- Compra de produtos extras (convites extras, mesas extras, álbuns, etc.)
- Login via `portal_users` (e-mail + senha), independente do backoffice
- Acesso multi-formando (um usuário do portal vinculado a múltiplos formandos)
- Recuperação e troca de senha

### 3.2 Backoffice Administrativo

- Dashboard gerencial com KPIs, gráficos e alertas
- CRUD completo de contratos, turmas, instituições, cursos e períodos
- CRUD de pacotes, produtos extras, categorias e termos de adesão
- Configuração de programações de valor e parcelamento (cronograma automático com validação de sobreposição)
- Configuração de condições de pagamento, descontos escalonados e modalidade híbrida
- Configurações globais do sistema (vencimentos de boleto, margens, valor mínimo, etc.)
- Gestão de formandos aderidos: ficha completa, pacotes, extrato financeiro
- Gestão de parcelas: baixa manual, reemissão, cancelamento, alteração de valor
- Gestão de usuários admin com perfis ACL customizáveis
- Tabelas auxiliares (índices de reajuste, categorias, cursos, períodos)
- Relatórios gerenciais e financeiros com exportação CSV/Excel
- Simulador de parcelamento para preview de cenários
- Logs de auditoria para todas as ações críticas
- Login independente do portal

---

## 4. Gestão de Contratos e Turmas

_(Mantém-se integralmente o conteúdo do PRD v2.1 — Seções 4.1 a 4.5, incluindo campos, tabelas auxiliares, regras de negócio de responsáveis e sistema de reajuste contratual.)_

O contrato é a entidade central do sistema. Ele representa o acordo entre a empresa organizadora e a instituição de ensino, e a ele se vinculam turmas, cursos, pacotes, condições de pagamento e formandos.

### 4.1 Campos do Contrato

| Campo                              | Tipo                | Obrigatório | Descrição                                                                  |
| ---------------------------------- | ------------------- | :---------: | -------------------------------------------------------------------------- |
| `id`                               | BIGINT (PK)         |     Sim     | Identificador único                                                        |
| `codigo_turma`                     | VARCHAR(20) UNIQUE  |     Sim     | Código público que o formando usa para acessar a tela de adesão            |
| `token`                            | VARCHAR(255) UNIQUE |     Sim     | Token criptografado (hash) para acesso ao contrato via URL segura          |
| `instituicao_id`                   | FK → instituicoes   |     Sim     | Instituição de ensino vinculada                                            |
| `nome_turma`                       | VARCHAR(255)        |     Sim     | Nome/identificação da turma                                                |
| `mes_conclusao`                    | SMALLINT (1-12)     |     Sim     | Mês de conclusão do curso                                                  |
| `ano_conclusao`                    | SMALLINT            |     Sim     | Ano de conclusão do curso                                                  |
| `data_inicio_contrato`             | DATE                |     Sim     | Data de início do contrato                                                 |
| `data_evento`                      | DATE                |     Não     | Data prevista do evento de formatura                                       |
| `meta_formandos`                   | INTEGER             |     Não     | Meta de quantidade de formandos para o contrato                            |
| `exige_responsavel_cadastro`       | BOOLEAN             |     Sim     | Se ativo, obriga cadastro de responsável pelo formando                     |
| `exige_responsavel_financeiro`     | BOOLEAN             |     Sim     | Se ativo, todas as cobranças são geradas no nome do responsável financeiro |
| `permite_formando_resp_financeiro` | BOOLEAN             |     Sim     | Se ativo E formando ≥18, permite "Formando é o responsável financeiro"     |
| `permite_formando_resp_cadastro`   | BOOLEAN             |     Sim     | Se ativo E formando ≥18, permite "Formando é o responsável de cadastro"    |
| `status`                           | ENUM                |     Sim     | `ativo`, `cancelado`, `concluido`                                          |
| `observacoes`                      | TEXT                |     Não     | Observações gerais                                                         |
| `created_at`                       | TIMESTAMP           |    Auto     | —                                                                          |
| `updated_at`                       | TIMESTAMP           |    Auto     | —                                                                          |

**Regras de negócio dos responsáveis:**

- Se `exige_responsavel_cadastro = true`: o formando DEVE cadastrar um responsável de cadastro OU, se for maior de idade e `permite_formando_resp_cadastro = true`, pode marcar a flag "Formando é o próprio responsável de cadastro".
- Se `exige_responsavel_financeiro = true`: o formando DEVE cadastrar um responsável financeiro OU, se for maior de idade e `permite_formando_resp_financeiro = true`, pode marcar a flag "Formando é o responsável financeiro".
- Se ambos estiverem ativos, ambos devem ser preenchidos (ou usar as flags se elegível).
- A verificação de maioridade é feita pela data de nascimento do formando no momento do cadastro.

### 4.2 a 4.5

_(Tabelas de Instituição, Cursos, Períodos e Reajustes mantêm-se conforme PRD v2.1.)_

---

## 5. Cadastro de Pacotes e Produtos Extras

_(Mantém-se integralmente conforme PRD v2.1 — Seções 5.1 a 5.4, incluindo categorias, contrato_produtos, vinculação de termos e exibição de termos na listagem.)_

---

## 6. Programações de Valor e Parcelamento

_(Mantém-se integralmente conforme PRD v2.1 — Seções 6.1 a 6.6, incluindo conceito, tabela produto_programacoes, exemplo prático, validação de sobreposição, programações de desconto e interface no backoffice.)_

---

## 7. Sistema de Descontos e Condições de Pagamento

_(Mantém-se integralmente conforme PRD v2.1 — Seções 7.1 a 7.3.)_

---

## 8. Configurações Globais do Sistema

_(Mantém-se integralmente conforme PRD v2.1 — Seções 8.1 a 8.5.)_

---

## 9. Cálculo Dinâmico de Parcelas e Vencimentos

_(Mantém-se integralmente conforme PRD v2.1 — Seções 9.1 a 9.5.)_

---

## 10. Termos de Adesão

_(Mantém-se integralmente conforme PRD v2.1 — Seções 10.1 a 10.4.)_

---

## 11. Autenticação, Multi-Formando e Controle de Acesso

_(Mantém-se integralmente conforme PRD v2.1 — Seções 11.1 a 11.10.)_

---

## 12. Fluxo de Adesão do Formando (Portal)

_(Mantém-se integralmente conforme PRD v2.1 — Seções 12.1 a 12.2.)_

---

## 13. Área do Formando (Portal)

_(Mantém-se integralmente conforme PRD v2.1 — Seções 13.1 a 13.6.)_

---

## 14. Backoffice Administrativo — Detalhamento Completo de Telas e Recursos

Esta seção detalha **todas as telas do painel administrativo**, seus componentes, ações disponíveis, filtros, indicadores visuais e fluxos de interação. O admin utiliza o template **Inspinia com Tailwind CSS 4** integrado ao **Laravel 13**, aproveitando os componentes nativos do Inspinia (datatables, modais, drawers, tabs, cards com KPIs, toasts, alerts, dropdowns, wizards, charts).

### 14.1 Tela: Login Admin

**Rota:** `/admin/login`

**Descrição:** Tela de autenticação exclusiva para administradores, completamente separada do portal do formando.

**Componentes e comportamentos:**

- Campo de e-mail com validação de formato em tempo real
- Campo de senha com toggle de visibilidade (olho/olho cortado)
- Checkbox "Lembrar-me" (remember_token)
- Botão "Entrar" com loading state e desabilitação durante requisição
- Link "Esqueci minha senha" (funcionalidade de recuperação de senha admin)
- Feedback de erro: "Credenciais inválidas" ou "Usuário inativo" com toast vermelho do Inspinia
- Após login bem-sucedido, redireciona para `/admin/dashboard`
- Registro de `last_login_at` e IP do login

**Regras:**

- Admin com `ativo = false` não consegue logar (mensagem: "Sua conta está desativada. Contate o administrador.")
- Limite de tentativas de login: 5 tentativas em 10 minutos (throttle padrão Laravel)

---

### 14.2 Tela: Dashboard Administrativo

**Rota:** `/admin`

**Descrição:** Visão gerencial consolidada de todo o sistema. Primeira tela após o login. Utiliza cards com KPIs, gráficos Inspinia (ApexCharts) e tabelas resumidas.

**Layout — KPI Cards (topo da página, grid 4 colunas):**

| Card                    | Valor                                                  | Ícone           | Cor      |
| ----------------------- | ------------------------------------------------------ | --------------- | -------- |
| Contratos Ativos        | Contagem de contratos com `status = ativo`             | Ícone documento | Azul     |
| Formandos Aderidos      | Total de adesões com `status = ativa`                  | Ícone pessoas   | Verde    |
| Receita Total a Receber | Soma de `valor_cobrado` de parcelas pendentes/vencidas | Ícone dinheiro  | Amarelo  |
| Inadimplência           | Percentual de parcelas vencidas sobre total            | Ícone alerta    | Vermelho |

**Seção: Gráficos (grid 2 colunas):**

- **Gráfico 1 — Adesões por Mês:** Gráfico de barras (ApexCharts) mostrando o número de adesões nos últimos 12 meses. Filtro por contrato disponível.
- **Gráfico 2 — Receita x Inadimplência:** Gráfico de linhas duplas mostrando evolução da receita recebida vs. inadimplência nos últimos 12 meses. Filtro por contrato disponível.

**Seção: Meta de Formandos por Contrato (tabela resumida):**

| Coluna     | Descrição                                                        |
| ---------- | ---------------------------------------------------------------- |
| Contrato   | Nome da turma + instituição                                      |
| Meta       | `meta_formandos` configurada                                     |
| Aderidos   | Contagem de adesões ativas                                       |
| % Atingido | Percentual visual com barra de progresso (Inspinia progress bar) |
| Status     | Badge colorido: verde (≥80%), amarelo (50-79%), vermelho (<50%)  |

**Seção: Últimas Adesões (tabela, 10 registros):**

| Coluna      | Descrição                                                |
| ----------- | -------------------------------------------------------- |
| Formando    | Nome completo                                            |
| Contrato    | Código da turma                                          |
| Pacote      | Nome do pacote principal                                 |
| Data Adesão | Formatada dd/mm/yyyy HH:mm                               |
| Valor Total | Valor final da adesão                                    |
| Ação        | Botão "Ver Ficha" que redireciona para ficha do formando |

**Seção: Parcelas Vencendo nos Próximos 7 Dias (tabela, 10 registros):**

| Coluna     | Descrição                                          |
| ---------- | -------------------------------------------------- |
| Formando   | Nome completo                                      |
| Parcela    | Nº da parcela / total (ex: 3/12)                   |
| Valor      | Valor cobrado                                      |
| Vencimento | Data formatada com indicador visual de proximidade |
| Modalidade | Badge: Boleto / Cartão / PIX                       |
| Ação       | Botão "Ver Extrato"                                |

**Seção: Alertas do Sistema (sidebar ou card lateral):**

- Contratos sem programação ativa para algum produto
- Programações que vencem nos próximos 15 dias
- Número de parcelas vencidas sem tratamento
- Reajustes pendentes de aplicação

---

### 14.3 Tela: Gestão de Instituições

**Rota:** `/admin/instituicoes`

**Descrição:** CRUD completo de instituições de ensino. Utiliza DataTable Inspinia com busca, filtros e paginação.

**Listagem — Colunas da DataTable:**

| Coluna        | Filtro                           | Ordenação |
| ------------- | -------------------------------- | --------- |
| Logo          | —                                | —         |
| Razão Social  | Busca texto                      | Sim       |
| Nome Fantasia | Busca texto                      | Sim       |
| CNPJ          | Busca texto                      | —         |
| Cidade/UF     | Select de UF                     | Sim       |
| Status        | Toggle ativo/inativo             | Sim       |
| Contratos     | Contagem de contratos vinculados | Sim       |
| Ações         | Editar, Inativar/Ativar          | —         |

**Ações da listagem:**

- Botão "Nova Instituição" (topo direito) → abre formulário de criação
- Cada linha possui dropdown de ações: "Editar" e "Inativar" (ou "Ativar" se inativa)
- Inativar com confirmação modal: "Esta instituição possui X contrato(s) vinculado(s). Deseja realmente inativá-la?"
- Exportação da lista em CSV

**Formulário de Criação/Edição (`/admin/instituicoes/novo` ou `/admin/instituicoes/{id}`):**

| Campo               | Tipo Input             | Validação                           | Observação                                           |
| ------------------- | ---------------------- | ----------------------------------- | ---------------------------------------------------- |
| Razão Social        | Text input             | Obrigatório, máx 255                | —                                                    |
| Nome Fantasia       | Text input             | Obrigatório, máx 255                | —                                                    |
| CNPJ                | Text input com máscara | Obrigatório, validação CNPJ, unique | Máscara XX.XXX.XXX/XXXX-XX                           |
| CEP                 | Text input com máscara | Obrigatório, consulta automática    | Via API ViaCEP, preenche logradouro/bairro/cidade/UF |
| Logradouro          | Text input             | Obrigatório                         | Auto-preenchido pelo CEP                             |
| Número              | Text input             | Obrigatório                         | —                                                    |
| Complemento         | Text input             | Opcional                            | —                                                    |
| Bairro              | Text input             | Obrigatório                         | Auto-preenchido pelo CEP                             |
| Cidade              | Text input             | Obrigatório                         | Auto-preenchido pelo CEP                             |
| UF                  | Select                 | Obrigatório                         | Auto-preenchido pelo CEP                             |
| Telefone            | Text input com máscara | Opcional                            | —                                                    |
| E-mail              | Text input             | Opcional, formato e-mail            | —                                                    |
| Contato Responsável | Text input             | Opcional                            | Nome do contato na instituição                       |
| Logo                | Upload de imagem       | Opcional                            | JPG/PNG, máx 2MB, preview                            |
| Ativo               | Toggle switch          | —                                   | Padrão: ativo                                        |

**Botões:** "Salvar" (com loading state), "Cancelar" (volta para listagem)

---

### 14.4 Tela: Gestão de Contratos

**Rota:** `/admin/contratos`

**Descrição:** CRUD principal do sistema. Cada contrato possui subtelas para cursos, períodos, reajustes, pacotes/produtos.

**Listagem — Colunas da DataTable:**

| Coluna          | Filtro                             | Ordenação |
| --------------- | ---------------------------------- | --------- |
| Código da Turma | Busca texto                        | Sim       |
| Nome da Turma   | Busca texto                        | Sim       |
| Instituição     | Select dropdown                    | Sim       |
| Conclusão       | Mês/Ano (ex: 12/2026)              | Sim       |
| Data Evento     | —                                  | Sim       |
| Meta Formandos  | —                                  | Sim       |
| Aderidos        | Contagem de adesões ativas         | Sim       |
| Status          | Select (ativo/cancelado/concluído) | Sim       |
| Ações           | Editar, Duplicar, Ver, Inativar    | —         |

**Ações da listagem:**

- Botão "Novo Contrato" (topo) → formulário de criação
- Dropdown de ações por linha: "Editar", "Duplicar Contrato", "Ver Formandos", "Cancelar"
- Duplicação gera novo contrato com todos os dados copiados (pacotes, programações, condições, descontos) exceto adesões

**Formulário de Criação/Edição (`/admin/contratos/novo` ou `/admin/contratos/{id}`):**

Layout em **tabs** (componente Inspinia Tabs):

**Tab 1 — Dados do Contrato:**

| Campo                | Tipo Input                                | Validação                                                   |
| -------------------- | ----------------------------------------- | ----------------------------------------------------------- |
| Código da Turma      | Text input                                | Obrigatório, máx 20, unique, somente alfanumérico maiúsculo |
| Instituição          | Select searchable (Inspinia / Choices.js) | Obrigatório, FK                                             |
| Nome da Turma        | Text input                                | Obrigatório                                                 |
| Mês de Conclusão     | Select (1-12)                             | Obrigatório                                                 |
| Ano de Conclusão     | Number input                              | Obrigatório, min ano atual                                  |
| Data Início Contrato | Datepicker (Flatpickr)                    | Obrigatório                                                 |
| Data do Evento       | Datepicker                                | Opcional                                                    |
| Meta de Formandos    | Number input                              | Opcional                                                    |
| Observações          | Textarea                                  | Opcional                                                    |
| Status               | Select                                    | Obrigatório                                                 |

**Tab 2 — Configurações de Responsáveis:**

| Campo                                          | Tipo          | Descrição visual                 |
| ---------------------------------------------- | ------------- | -------------------------------- |
| Exige Responsável de Cadastro                  | Toggle switch | Com tooltip explicativo          |
| Permite Formando ser Resp. Cadastro (se ≥18)   | Toggle switch | Só visível se "Exige" está ativo |
| Exige Responsável Financeiro                   | Toggle switch | Com tooltip explicativo          |
| Permite Formando ser Resp. Financeiro (se ≥18) | Toggle switch | Só visível se "Exige" está ativo |

Preview em tempo real: "Com esta configuração, o formando de 17 anos será obrigado a cadastrar um responsável de cadastro e um responsável financeiro."

**Tab 3 — Cursos (subtela inline):**

- Tabela inline com os cursos do contrato
- Botão "Adicionar Curso" → input inline ou modal rápido
- Campos: Nome do Curso, Ativo (toggle)
- Ações por linha: Editar (inline), Remover (com confirmação se não houver adesões vinculadas)

**Tab 4 — Períodos (subtela inline):**

- Mesmo formato dos cursos
- Campos: Nome do Período (ex: Matutino, Noturno), Ativo
- Ações: Editar (inline), Remover

**Tab 5 — Reajustes:**

- Tabela mostrando todos os reajustes do contrato
- Colunas: Índice (IGPM/IPCA/INPC), Ano/Mês Referência, Percentual, Data Aplicação, Status (Pendente/Aplicado)
- Botão "Novo Reajuste" → modal com: select do índice, ano/mês referência, percentual, data de aplicação
- Botão "Aplicar Reajuste" (para pendentes com data ≤ hoje) → confirmação: "Ao aplicar, as parcelas em aberto serão recalculadas. Deseja continuar?"
- Log de aplicação visível na tabela

---

### 14.5 Tela: Gestão de Categorias de Produtos

**Rota:** `/admin/categorias`

**Descrição:** Tabela auxiliar global para classificar pacotes e produtos.

**Listagem:** DataTable simples com colunas: Nome, Descrição, Ativo, Ações (Editar, Inativar).

**Formulário:** Modal do Inspinia (drawer lateral) com campos: Nome, Descrição, Ativo.

---

### 14.6 Tela: Gestão de Pacotes e Produtos

**Rota:** `/admin/produtos`

**Descrição:** Tela principal de gestão de pacotes/produtos, com acesso às sub-configurações de cada produto.

**Listagem — Colunas da DataTable:**

| Coluna            | Filtro                                  | Ordenação |
| ----------------- | --------------------------------------- | --------- |
| Imagem            | —                                       | —         |
| Nome              | Busca texto                             | Sim       |
| Contrato          | Select dropdown                         | Sim       |
| Categoria         | Select dropdown                         | Sim       |
| Período Venda     | Data início/fim                         | Sim       |
| Programação Ativa | Valor + parcelas da programação vigente | —         |
| Disponível Adesão | Badge Sim/Não                           | Sim       |
| Grupo Exclusivo   | Badge com nome do grupo                 | Sim       |
| Status            | Toggle                                  | Sim       |
| Ações             | Menu dropdown                           | —         |

**Ações por produto (dropdown):**

- Editar Produto
- Gerenciar Programações (subtela)
- Gerenciar Condições de Pagamento (subtela)
- Gerenciar Descontos (subtela)
- Gerenciar Termos (subtela)
- Inativar

**Formulário de Criação/Edição:**

Layout com tabs:

**Tab 1 — Dados do Produto:**

| Campo                | Tipo                             | Validação                         |
| -------------------- | -------------------------------- | --------------------------------- |
| Contrato             | Select searchable                | Obrigatório                       |
| Categoria            | Select                           | Obrigatório                       |
| Nome                 | Text                             | Obrigatório                       |
| Descrição            | Textarea rica (ou texto simples) | Opcional                          |
| Imagem               | Upload (JPG/PNG, máx 2MB)        | Opcional, com preview             |
| Data Início Venda    | Datepicker                       | Obrigatório                       |
| Data Fim Venda       | Datepicker                       | Obrigatório, > início             |
| Disponível na Adesão | Toggle                           | Padrão: sim                       |
| Grupo Exclusivo      | Text input                       | Opcional, com tooltip explicativo |
| Ordem Exibição       | Number                           | Opcional                          |

**Tab 2 — Programações de Valor:**

Subtela inline (ver Seção 14.7).

**Tab 3 — Condições de Pagamento:**

Subtela inline (ver Seção 14.8).

**Tab 4 — Descontos:**

Subtela inline (ver Seção 14.9).

**Tab 5 — Termos Vinculados:**

Subtela inline (ver Seção 14.10).

---

### 14.7 Subtela: Programações de Valor/Parcelamento

**Rota:** `/admin/produtos/{id}/programacoes` (ou tab inline)

**Descrição:** Gerenciamento do cronograma de valores e parcelas máximas do produto. Cada produto pode ter múltiplas programações em períodos distintos sem sobreposição.

**Listagem (formato timeline/tabela):**

| Coluna        | Descrição                                                  |
| ------------- | ---------------------------------------------------------- |
| Período       | Data início — Data fim (com badge "Ativa" se vigente hoje) |
| Valor         | R$ formatado                                               |
| Parcelas Máx. | Número                                                     |
| Descrição     | Texto livre (ex: "Fase 1 — Promocional")                   |
| Status        | Ativo/Inativo                                              |
| Ações         | Editar, Inativar                                           |

**Indicadores visuais:**

- Programação vigente hoje: badge verde "ATIVA"
- Programações futuras: badge azul "FUTURA"
- Programações passadas: badge cinza "EXPIRADA"
- Alerta amarelo se houver gap (período sem programação entre duas programações)

**Formulário (Modal Inspinia):**

| Campo            | Tipo                        | Validação             |
| ---------------- | --------------------------- | --------------------- |
| Data Início      | Datepicker                  | Obrigatório           |
| Data Fim         | Datepicker                  | Obrigatório, > início |
| Valor (R$)       | Input monetário com máscara | Obrigatório, > 0      |
| Parcelas Máximas | Number input                | Obrigatório, ≥ 1      |
| Descrição        | Text                        | Opcional              |

**Validação em tempo real:** Ao informar as datas, o sistema verifica sobreposição com programações ativas existentes. Se houver sobreposição, exibe alerta vermelho: "Conflito com programação existente de [data_inicio] a [data_fim]."

**Preview:** "Se um formando aderir em [hoje], pagará R$ [valor] em até [N]x."

---

### 14.8 Subtela: Condições de Pagamento

**Rota:** `/admin/produtos/{id}/condicoes` (ou tab inline)

**Listagem:**

| Coluna                  | Descrição                    |
| ----------------------- | ---------------------------- |
| Modalidade              | Badge: Boleto / Cartão / PIX |
| Parcela Mín.            | Número                       |
| Parcela Máx.            | Número                       |
| Híbrida                 | Sim/Não                      |
| Data Limite Híbrida     | —                            |
| Modalidade Complementar | —                            |
| Status                  | Toggle                       |

**Formulário (Modal):**

| Campo                   | Tipo                       | Validação                  |
| ----------------------- | -------------------------- | -------------------------- |
| Modalidade              | Select (boleto/cartão/PIX) | Obrigatório                |
| Parcela Mínima          | Number                     | Obrigatório, ≥ 1           |
| Parcela Máxima          | Number                     | Obrigatório, ≥ parcela_min |
| Modalidade Híbrida      | Toggle                     | —                          |
| Data Limite Híbrida     | Datepicker                 | Obrigatório se híbrida     |
| Modalidade Complementar | Select                     | Obrigatório se híbrida     |

---

### 14.9 Subtela: Descontos

**Rota:** `/admin/produtos/{id}/descontos` (ou tab inline)

**Listagem:**

| Coluna             | Descrição              |
| ------------------ | ---------------------- |
| Condição Pagamento | Modalidade vinculada   |
| Faixa de Parcelas  | De X a Y               |
| Desconto           | Percentual             |
| Vigência           | Data início — Data fim |
| Status             | Toggle                 |

**Formulário (Modal):**

| Campo                  | Tipo                                     | Validação                 |
| ---------------------- | ---------------------------------------- | ------------------------- |
| Condição de Pagamento  | Select (entre as cadastradas do produto) | Obrigatório               |
| Parcela De             | Number                                   | Obrigatório, ≥ 1          |
| Parcela Até            | Number                                   | Obrigatório, ≥ parcela_de |
| Percentual de Desconto | Number com 2 decimais                    | Obrigatório, 0.01 a 100   |
| Data Início Vigência   | Datepicker                               | Obrigatório               |
| Data Fim Vigência      | Datepicker                               | Obrigatório, > início     |

**Validação de sobreposição:** Mesma lógica das programações — não permite faixas ativas com mesmo período e mesma faixa de parcelas para mesma condição.

---

### 14.10 Subtela: Termos do Produto

**Rota:** `/admin/produtos/{id}/termos` (ou tab inline)

**Descrição:** Vinculação de termos existentes ao produto, com definição de ordem de exibição.

**Listagem:**

| Coluna | Descrição                             |
| ------ | ------------------------------------- |
| Ordem  | Número (drag-and-drop para reordenar) |
| Termo  | Nome do termo                         |
| Versão | Versão atual do termo                 |
| Ações  | Remover vínculo, Ver prévia           |

**Ações:**

- Botão "Vincular Termo" → Select searchable com termos disponíveis
- Reordenação drag-and-drop que atualiza `produto_termos.ordem`
- Botão "Preview PDF Consolidado" → gera e exibe o PDF com todos os termos na ordem definida (com placeholders)

---

### 14.11 Tela: Gestão de Termos

**Rota:** `/admin/termos`

**Descrição:** CRUD de termos de adesão com editor WYSIWYG (TinyMCE), controle de versão e preview.

**Listagem:**

| Coluna              | Filtro                    | Ordenação |
| ------------------- | ------------------------- | --------- |
| Nome                | Busca texto               | Sim       |
| Versão              | —                         | Sim       |
| Produtos Vinculados | Contagem                  | —         |
| Última Atualização  | —                         | Sim       |
| Status              | Toggle                    | Sim       |
| Ações               | Editar, Preview, Duplicar | —         |

**Formulário de Criação/Edição (`/admin/termos/novo` ou `/admin/termos/{id}`):**

Layout full-width para editor:

| Campo     | Tipo                 | Validação                       |
| --------- | -------------------- | ------------------------------- |
| Nome      | Text                 | Obrigatório                     |
| Descrição | Text                 | Opcional                        |
| Versão    | Text                 | Obrigatório (ex: 1.0, 1.1, 2.0) |
| Conteúdo  | TinyMCE WYSIWYG full | Obrigatório                     |

**Barra de variáveis (acima do editor):** Botões clicáveis com cada variável disponível. Ao clicar, insere a variável na posição do cursor do TinyMCE:

`{{nome_formando}}` `{{cpf_formando}}` `{{rg_formando}}` `{{endereco_formando}}` `{{nome_pacote}}` `{{valor_pacote}}` `{{valor_com_desconto}}` `{{valor_parcela}}` `{{qtd_parcelas}}` `{{forma_pagamento}}` `{{nome_instituicao}}` `{{nome_turma}}` `{{curso}}` `{{periodo}}` `{{ano_conclusao}}` `{{data_evento}}` `{{nome_responsavel_financeiro}}` `{{cpf_responsavel_financeiro}}` `{{nome_responsavel_cadastro}}` `{{data_adesao}}` `{{codigo_turma}}`

**Botão "Preview":** Abre modal com o termo renderizado usando dados de exemplo (dados fictícios pré-definidos para cada variável).

**Controle de versão:** Ao editar um termo que já possui aceites registrados, o sistema solicita incremento da versão e mantém o conteúdo anterior como histórico.

---

### 14.12 Tela: Gestão de Formandos

**Rota:** `/admin/formandos`

**Descrição:** Listagem geral e ficha completa de todos os formandos aderidos no sistema.

**Listagem — Colunas da DataTable:**

| Coluna        | Filtro                              | Ordenação |
| ------------- | ----------------------------------- | --------- |
| Foto          | —                                   | —         |
| Nome Completo | Busca texto                         | Sim       |
| CPF           | Busca texto                         | —         |
| Contrato      | Select dropdown                     | Sim       |
| Curso         | Select (dinâmico conforme contrato) | Sim       |
| Período       | Select                              | Sim       |
| Data Adesão   | Daterange picker                    | Sim       |
| Adimplência   | Select (em dia/inadimplente)        | Sim       |
| Ações         | Ver Ficha                           | —         |

**Filtros avançados (collapsable):**

- Contrato (select)
- Curso (select, dinâmico)
- Período (select, dinâmico)
- Status de adimplência (em dia / inadimplente)
- Data de adesão (range)
- Pacote aderido (select)

**Ficha Completa do Formando (`/admin/formandos/{id}`):**

Layout de página com sidebar de informações e tabs de conteúdo:

**Sidebar (fixa à esquerda):**

- Foto do formando (grande, com botão de troca)
- Nome completo
- CPF (badge copiável)
- Status: Badge "Em dia" (verde) ou "Inadimplente" (vermelho)
- Contrato: Código da turma + nome
- Curso e Período
- Data de adesão
- Botão "Editar Dados" (abre modal de edição)

**Tab 1 — Dados Pessoais:**

Exibição em cards de todos os dados do formando: nome, CPF, RG, data nascimento, gênero, endereço completo, telefone, e-mail.

Botão "Editar" que abre modal com formulário de edição. Campos CPF e data nascimento são somente leitura após adesão.

**Tab 2 — Responsáveis:**

Card para Responsável de Cadastro (se existir): todos os dados. Card para Responsável Financeiro (se existir): todos os dados. Se "Formando é o responsável", mostra badge informativo.

**Tab 3 — Portal Users Vinculados:**

Tabela com os `portal_users` que têm acesso a este formando:

| Coluna       | Descrição                                           |
| ------------ | --------------------------------------------------- |
| Nome         | Nome do portal_user                                 |
| E-mail       | E-mail de login                                     |
| Papel        | titular / resp_cadastro / resp_financeiro           |
| Último Login | Data/hora                                           |
| Ações        | Desvincular (com confirmação se é o último vínculo) |

Botão "Vincular Portal User" → busca por e-mail, vincula com papel selecionado.

**Tab 4 — Pacotes e Adesão:**

Para cada pacote/produto aderido:

| Informação            | Descrição                                                   |
| --------------------- | ----------------------------------------------------------- |
| Nome do Pacote        | Nome + categoria                                            |
| Programação Utilizada | Período e valor da programação vigente no momento da adesão |
| Valor Original        | R$ da programação                                           |
| Desconto              | Percentual + valor                                          |
| Valor Final           | R$ final                                                    |
| Modalidade            | Boleto / Cartão / PIX / Híbrida                             |
| Parcelas              | Qtd escolhida                                               |
| Dia Vencimento        | Dia base (se boleto)                                        |

**Tab 5 — Extrato Financeiro:**

Tabela de parcelas com todas as informações detalhadas:

| Coluna         | Descrição                                                                      |
| -------------- | ------------------------------------------------------------------------------ |
| #              | Número da parcela                                                              |
| Pacote         | Nome                                                                           |
| Valor Original | R$                                                                             |
| Reajuste       | R$ (se aplicado)                                                               |
| Desconto       | R$                                                                             |
| Valor Cobrado  | R$ final                                                                       |
| Vencimento     | Data                                                                           |
| Modalidade     | Badge                                                                          |
| Status         | Badge: Pendente (amarelo), Pago (verde), Vencido (vermelho), Cancelado (cinza) |
| Pgto em        | Data de pagamento                                                              |
| Ações          | Menu dropdown                                                                  |

**Ações por parcela (dropdown):**

- **Dar Baixa Manual** → Modal: valor pago, data pagamento, observação. Registra log de auditoria.
- **Reemitir Boleto** → Modal: nova data de vencimento. Gera novo boleto no gateway, atualiza URL.
- **Cancelar Parcela** → Modal: motivo do cancelamento. Registra log. Status muda para `cancelado`.
- **Alterar Valor** → Modal: novo valor, justificativa. Registra log com valor anterior e novo.

**Totalizadores (cards acima da tabela):**

| Card           | Descrição                         |
| -------------- | --------------------------------- |
| Total Geral    | Soma de todos os valores cobrados |
| Total Pago     | Soma de parcelas pagas            |
| Total Pendente | Soma de parcelas pendentes        |
| Total Vencido  | Soma de parcelas vencidas         |

**Tab 6 — Termos Aceitos:**

Tabela com registros de aceite:

| Coluna           | Descrição                                                     |
| ---------------- | ------------------------------------------------------------- |
| Termo            | Nome do termo                                                 |
| Pacote           | Pacote vinculado                                              |
| Versão           | Versão do termo no momento do aceite                          |
| Data/Hora Aceite | Timestamp                                                     |
| IP               | IP registrado                                                 |
| Ações            | "Ver Snapshot" (abre modal com HTML do termo conforme aceito) |

**Tab 7 — Histórico / Auditoria:**

Tabela de logs de todas as ações realizadas sobre este formando:

| Coluna    | Descrição                                    |
| --------- | -------------------------------------------- |
| Data/Hora | Timestamp                                    |
| Ator      | Nome do admin ou "Sistema"                   |
| Ação      | Descrição (ex: "Baixa manual da parcela #5") |
| Detalhes  | JSON com before/after (expansível)           |

---

### 14.13 Tela: Gestão Financeira / Parcelas

**Rota:** `/admin/financeiro/parcelas`

**Descrição:** Visão consolidada de todas as parcelas do sistema, com filtros avançados e ações em lote.

**Filtros (topo, collapsable):**

| Filtro     | Tipo                                              |
| ---------- | ------------------------------------------------- |
| Contrato   | Select searchable                                 |
| Formando   | Busca por nome/CPF                                |
| Status     | Multi-select (pendente, pago, vencido, cancelado) |
| Modalidade | Multi-select (boleto, cartão, PIX)                |
| Vencimento | Daterange picker                                  |
| Pacote     | Select                                            |

**DataTable:**

| Coluna            | Ordenação |
| ----------------- | --------- |
| Formando          | Sim       |
| Contrato          | Sim       |
| Parcela (N/Total) | Sim       |
| Valor Cobrado     | Sim       |
| Vencimento        | Sim       |
| Modalidade        | —         |
| Status            | —         |
| Pgto em           | Sim       |
| Ações             | —         |

**Ações em lote:**

- Seleção múltipla com checkbox
- "Dar Baixa em Lote" (para parcelas selecionadas, abre modal com data/observação unificada)
- "Exportar Selecionadas" (CSV/Excel)

**Exportações:**

- Botão "Exportar CSV" com os filtros aplicados
- Botão "Exportar Excel" com formatação

**KPIs (cards no topo):**

| Card              | Descrição                    |
| ----------------- | ---------------------------- |
| Total de Parcelas | Contagem total filtrada      |
| Valor Total       | Soma dos valores cobrados    |
| Recebido          | Soma das parcelas pagas      |
| A Receber         | Soma das pendentes           |
| Vencidas          | Soma e contagem das vencidas |

---

### 14.14 Tela: Simulador de Parcelamento

**Rota:** `/admin/financeiro/simulador`

**Descrição:** Ferramenta para o administrador simular cenários de adesão sem efetivamente criar uma adesão. Útil para atendimento ao formando e configuração de programações.

**Campos do simulador:**

| Campo                      | Tipo                                              |
| -------------------------- | ------------------------------------------------- |
| Contrato                   | Select searchable                                 |
| Produto                    | Select (carrega produtos do contrato selecionado) |
| Data de Adesão Simulada    | Datepicker                                        |
| Modalidade de Pagamento    | Select                                            |
| Dia de Vencimento (boleto) | Select (opções configuradas)                      |
| Número de Parcelas         | Slider ou number                                  |

**Resultado (card de resultado):**

- Programação vigente na data simulada: período, valor e parcelas máx.
- Parcelas disponíveis calculadas (após redução dinâmica e margem)
- Desconto aplicável (percentual e valor)
- Valor final e valor de cada parcela
- Data do primeiro e último vencimento
- Cronograma de parcelas (tabela com nº, valor, vencimento, modalidade)
- Se híbrida: detalhamento de quais parcelas em cada modalidade

---

### 14.15 Tela: Configurações Globais

**Rota:** `/admin/configuracoes`

**Descrição:** Tela de gerenciamento das configurações globais do sistema. Acesso restrito a perfis com permissão `configuracoes.editar`.

**Layout:** Cards agrupados por seção, usando o layout de formulário do Inspinia.

**Grupo: Boleto**

| Configuração                | Input                                                | Descrição Visual                        |
| --------------------------- | ---------------------------------------------------- | --------------------------------------- |
| Dias de Vencimento          | Tags input (Tagify) — permite adicionar/remover dias | Ex: [10] [20] [30]                      |
| Margem de Dias para Emissão | Number input                                         | Com tooltip explicando o comportamento  |
| Ajustar Fim de Mês          | Toggle                                               | Com preview: "Dia 30 em fevereiro → 28" |

**Grupo: Parcelas**

| Configuração            | Input           | Descrição Visual |
| ----------------------- | --------------- | ---------------- |
| Valor Mínimo de Parcela | Input monetário | Ex: R$ 50,00     |

**Grupo: E-mail**

| Configuração     | Input      | Descrição Visual                         |
| ---------------- | ---------- | ---------------------------------------- |
| Dias de Lembrete | Tags input | Ex: [5] [3] [1] dias antes do vencimento |

**Preview dinâmico (card na lateral ou abaixo):**

"Com as configurações atuais: Um formando que aderir hoje (17/03/2026) escolhendo vencimento dia 10 terá o primeiro boleto para 10/04/2026."

**Botão "Salvar Configurações"** com confirmação e registro de log de auditoria.

---

### 14.16 Tela: Gestão de Índices de Reajuste

**Rota:** `/admin/indices-reajuste`

**Descrição:** CRUD simples para tipos de índice econômico.

**Listagem:** DataTable com colunas: Nome (IGPM, IPCA, INPC...), Descrição, Ativo.

**Formulário (Modal):** Nome, Descrição, Ativo.

---

### 14.17 Tela: Relatórios

**Rota:** `/admin/relatorios`

**Descrição:** Central de relatórios gerenciais e financeiros.

**Relatórios disponíveis:**

| Relatório                   | Filtros                              | Formato                                |
| --------------------------- | ------------------------------------ | -------------------------------------- |
| Adesões por Período         | Contrato, data range                 | Tabela + gráfico, exportação CSV/Excel |
| Receita por Contrato        | Contrato, período                    | Tabela + gráfico                       |
| Inadimplência               | Contrato, período, threshold de dias | Tabela com formandos inadimplentes     |
| Recebimentos                | Período, modalidade, contrato        | Tabela detalhada + totalizadores       |
| Formandos por Curso/Período | Contrato                             | Tabela agrupada                        |
| Reajustes Aplicados         | Contrato, período                    | Tabela com antes/depois                |

Cada relatório possui botões de exportação (CSV, Excel) e possibilidade de impressão.

---

### 14.18 Tela: Gestão de Usuários Admin

**Rota:** `/admin/usuarios`

**Descrição:** CRUD de usuários administrativos do backoffice.

**Listagem:**

| Coluna       | Descrição                       |
| ------------ | ------------------------------- |
| Nome         | —                               |
| E-mail       | —                               |
| Perfil       | Nome do perfil ACL              |
| Último Login | Data/hora                       |
| Status       | Ativo/Inativo                   |
| Ações        | Editar, Resetar Senha, Inativar |

**Formulário (Modal ou Drawer):**

| Campo           | Tipo     | Validação                           |
| --------------- | -------- | ----------------------------------- |
| Nome            | Text     | Obrigatório                         |
| E-mail          | Text     | Obrigatório, unique, formato e-mail |
| Perfil          | Select   | Obrigatório                         |
| Senha (criação) | Password | Mín 8 caracteres                    |
| Ativo           | Toggle   | —                                   |

---

### 14.19 Tela: Gestão de Perfis e Permissões ACL

**Rota:** `/admin/perfis`

**Descrição:** Gerenciamento de perfis de acesso com permissões granulares.

**Listagem de Perfis:**

| Coluna    | Descrição                                |
| --------- | ---------------------------------------- |
| Nome      | Ex: Super Admin, Financeiro, Operacional |
| Descrição | —                                        |
| Usuários  | Contagem de admins com este perfil       |
| Ações     | Editar permissões, Inativar              |

**Tela de Edição de Permissões do Perfil:**

Matriz visual de permissões:

| Módulo         | Listar | Criar | Editar | Excluir | Exportar |
| -------------- | ------ | ----- | ------ | ------- | -------- |
| Contratos      | ☑     | ☑    | ☑     | ☐       | ☑       |
| Instituições   | ☑     | ☑    | ☑     | ☐       | ☑       |
| Produtos       | ☑     | ☑    | ☑     | ☐       | ☑       |
| Termos         | ☑     | ☑    | ☑     | ☐       | ☐        |
| Formandos      | ☑     | ☐     | ☑     | ☐       | ☑       |
| Financeiro     | ☑     | ☐     | ☑     | ☐       | ☑       |
| Configurações  | ☑     | ☐     | ☑     | ☐       | ☐        |
| Relatórios     | ☑     | ☐     | ☐      | ☐       | ☑       |
| Usuários Admin | ☑     | ☑    | ☑     | ☐       | ☐        |

Botão "Selecionar Todos" e "Limpar Todos" por módulo ou globalmente.

---

### 14.20 Tela: Cadastro Manual de Formando (Adesão pelo Admin)

**Rota:** `/admin/formandos/novo`

**Descrição:** Permite que o administrador faça a adesão de um formando manualmente (ex: atendimento presencial ou telefônico). O fluxo é equivalente ao wizard do portal, porém em formato de formulário compacto dentro do admin.

**Campos organizados em sections (Inspinia Accordion):**

- **Section 1:** Selecionar Contrato → Curso → Período
- **Section 2:** Dados do Formando (todos os campos)
- **Section 3:** Responsável de Cadastro (se exigido pelo contrato)
- **Section 4:** Responsável Financeiro (se exigido pelo contrato)
- **Section 5:** Conta do Portal (e-mail e senha do portal_user, ou vincular a existente)
- **Section 6:** Seleção de Pacotes/Produtos
- **Section 7:** Forma de Pagamento (com cálculo dinâmico em tempo real)
- **Section 8:** Resumo e Confirmação

**Observação:** Ao confirmar, o sistema registra que a adesão foi feita pelo admin (campo `origem = 'admin'` ou equivalente) e pula a etapa de aceite de termos no portal (o admin pode imprimir os termos para assinatura física).

---

### 14.21 Navegação do Admin (Sidebar Inspinia)

**Estrutura do menu lateral:**

```
📊 Dashboard
📋 Contratos
    ├── Todos os Contratos
    └── Novo Contrato
🏫 Instituições
📦 Pacotes & Produtos
    ├── Todos os Produtos
    ├── Categorias
    └── Termos de Adesão
👨‍🎓 Formandos
    ├── Todos os Formandos
    └── Cadastro Manual
💰 Financeiro
    ├── Parcelas
    ├── Simulador
    └── Relatórios
⚙️ Configurações
    ├── Configurações Globais
    ├── Índices de Reajuste
    ├── Usuários Admin
    └── Perfis & Permissões
📋 Logs de Auditoria
```

**Elementos do header:**

- Logo da empresa (configurável)
- Breadcrumb com navegação
- Nome do admin logado + dropdown: "Meu Perfil", "Trocar Senha", "Sair"
- Notificações (ícone sino com badge de contagem)
- Toggle tema claro/escuro

---

## 15. Sistema de Pagamentos e Integração

_(Mantém-se integralmente conforme PRD v2.1 — Seções 15.1 a 15.4, incluindo tabelas parcelas, adesoes, adesao_produtos e responsaveis.)_

---

## 16. E-mails Transacionais e Notificações

_(Mantém-se integralmente conforme PRD v2.1 — Seção 16.1.)_

---

## 17. Modelo de Dados Completo

_(Mantém-se integralmente conforme PRD v2.1 — Seções 17.1 e 17.2.)_

**Tabelas adicionadas nesta versão:**

27. `adesao_drafts` — Rascunhos do wizard de adesão (persistência entre etapas)
28. `audit_logs` — Logs de auditoria do sistema
29. `email_logs` — Logs de e-mails enviados
30. `pagamentos` — Registros detalhados de transações no gateway
31. `pagamento_eventos` — Eventos de cada tentativa de pagamento

---

## 18. Requisitos Não-Funcionais

_(Mantém-se integralmente conforme PRD v2.1 — Seção 18, com adições:)_

**Auditoria (expandido):**

- Tabela `audit_logs` com campos: actor_type, actor_id, modulo, acao, entity_type, entity_id, before_json, after_json, ip, user_agent, created_at
- Toda alteração em: parcelas (valor, status), dados de formandos, baixas manuais, reemissões, reajustes, configurações globais, permissões ACL
- Logs acessíveis na ficha do formando (tab Histórico) e em tela dedicada de logs

**Acessibilidade (novo):**

- Contraste adequado para leitura (WCAG AA mínimo no portal)
- Labels semânticos em todos os inputs
- Navegação por teclado nos formulários

---

## 19. Tecnologias e Stack

| Camada                      | Tecnologia                                                                   |
| --------------------------- | ---------------------------------------------------------------------------- |
| Backend                     | Laravel 13 (PHP 8.4+)                                                        |
| Banco de Dados              | PostgreSQL 16                                                                |
| Frontend Admin (Backoffice) | Livewire 3 + Inspinia + Tailwind CSS 4                                       |
| Frontend Portal             | Livewire 3 + Tailwind CSS 4 + Preline UI ou Tailwind puro (decisão pendente) |
| Autenticação                | Laravel Breeze com guards separados (portal_users e admin_users)             |
| Gateway de Pagamento        | API Itaú (Boleto, Cartão, PIX)                                               |
| Editor WYSIWYG              | TinyMCE (termos de adesão)                                                   |
| Geração de PDF              | DomPDF ou Snappy (consolidação de termos)                                    |
| E-mail                      | Laravel Mail (SMTP ou serviço transacional)                                  |
| Cache                       | Redis                                                                        |
| Filas                       | Laravel Queue (Redis)                                                        |
| Servidor                    | A definir                                                                    |

### 19.1 Template do Portal do Formando

O portal do formando é a interface pública e deve transmitir modernidade, confiança e ser intuitivo para formandos de todas as idades e seus pais. Como o admin usa Inspinia, o portal deve ter identidade visual própria.

**Opção A: Preline UI (Tailwind CSS) — em avaliação**

O Preline UI é um framework open-source baseado em Tailwind CSS com componentes prontos para wizards multi-step, cards de preço, dashboards, formulários e layouts responsivos. É compatível com Livewire e não adiciona dependência pesada.

Vantagens: componentes de wizard nativo (ideal para as 7 etapas da adesão), pricing cards (para exibição de pacotes), dashboard components (para área do formando), formulários com validação visual, 100% Tailwind (mesma stack do admin), gratuito e bem documentado.

**Opção B: Tailwind puro com componentes custom — em avaliação**

Design totalmente sob medida usando apenas Tailwind CSS 4 e componentes Blade criados do zero. Dá mais controle sobre identidade visual mas exige mais tempo de desenvolvimento.

**Decisão:** A ser definida antes da Sprint 4 (início do desenvolvimento do portal).

### 19.2 Template do Backoffice Administrativo

**Decisão definitiva: Inspinia Multipurpose Admin Dashboard Template (Tailwind CSS 4)**

O Inspinia é um template premium com 235+ páginas, 15+ apps, múltiplas skins, suporte a Laravel 13 e Tailwind CSS 4. Oferece DataTables, modais, drawers, tabs, cards KPI, gráficos (ApexCharts), wizards, formulários ricos e páginas de autenticação.

Skins recomendadas: SaaS ou Default. Plugins incluídos: ApexCharts, Flatpickr, Choices.js, Inputmask, SortableJS, Dropzone, SweetAlert2, Tagify.

O mapeamento detalhado do que usar e ignorar do Inspinia está documentado em `.docs/TEMPLATE-MAP-AND-COMPONENTS.md`.

---

## 20. Cronograma — Sprints de 7 Dias (Portal-First)

### 20.1 Visão Geral

- **Metodologia:** Sprints de 7 dias (1 semana)
- **Estratégia:** Portal-First — o Portal do Formando é construído e validado antes do Backoffice Administrativo
- **Foco:** Entregas pequenas e focadas para validação contínua do cliente
- **Total estimado:** 26 sprints + implantação
- **Premissa:** Cada sprint possui um escopo reduzido e bem definido, facilitando o foco em detalhes e reduzindo o volume de validação por entrega

### 20.2 Fases do Projeto

| Fase                              | Sprints | Descrição                                                            |
| --------------------------------- | ------- | -------------------------------------------------------------------- |
| **Fundação**                      | 1 a 3   | Setup do projeto, migrations, models, seeders, enums, factories      |
| **Portal — Adesão**               | 4 a 9   | Layout portal, wizard de adesão (7 etapas) completo com gateway mock |
| **Portal — Área do Formando**     | 10 a 11 | Login, multi-formando, dashboard, extrato, extras                    |
| **Gateway Itaú**                  | 12 a 13 | Integração real: Boleto, PIX, Cartão, Webhooks                       |
| **E-mails + Refinamentos Portal** | 14      | E-mails transacionais, automações, ajustes de UX                     |
| **Admin — Core**                  | 15 a 19 | Auth, ACL, CRUDs (instituições, contratos, produtos, termos)         |
| **Admin — Financeiro**            | 20 a 23 | Formandos, parcelas, simulador, relatórios, configurações            |
| **Admin — Finalização**           | 24      | Usuários admin, perfis ACL, logs auditoria                           |
| **Homologação**                   | 25 a 26 | Testes, ajustes, implantação                                         |

### 20.3 Detalhamento das Sprints

---

#### FASE 1 — FUNDAÇÃO (Sprints 1 a 3)

---

**Sprint 1 — Setup do Projeto e Estrutura Base**

Entregas:

- Criação do projeto Laravel 13 com estrutura de pastas definida no Blueprint
- Configuração do PostgreSQL, Redis, filas e ambiente de desenvolvimento
- Instalação e configuração do Inspinia + Tailwind CSS 4 no admin (layout shell)
- Instalação e configuração do template do portal (Preline UI ou Tailwind puro — conforme decisão)
- Setup dos dois guards de autenticação (portal e admin)
- Definição dos arquivos de rotas separados (portal.php, admin.php)
- Configuração dos dois layouts principais (admin e portal)

Validação do cliente: Ambiente rodando com as duas áreas acessíveis (layout base de cada uma).

---

**Sprint 2 — Migrations e Models (Grupo 1: Sistema, ACL e Estrutura Comercial)**

Entregas:

- Migrations: admin_perfis, admin_permissoes, perfil_permissoes, admin_users, portal_users, configuracoes_globais, audit_logs, email_logs
- Migrations: instituicoes, contratos, contrato_cursos, contrato_periodos, indices_reajuste, contrato_reajustes, categorias_produto, termos, contrato_produtos, produto_termos, produto_programacoes, produto_condicoes_pagamento, produto_descontos
- Models correspondentes com relacionamentos
- Enums: ContratoStatus, ModalidadePagamento, PapelPortalUserFormando, StatusAdesao, StatusParcela, TipoConfiguracao, TipoResponsavel, OrigemAdesao

Validação do cliente: Banco de dados parcial criado, migrations rodando.

---

**Sprint 3 — Migrations (Grupo 2: Pessoas e Financeiro) + Seeders Robustos**

Entregas:

- Migrations: formandos, portal_user_formandos, responsaveis, adesoes, adesao_produtos, aceites_termos, parcelas, pagamentos, pagamento_eventos, adesao_drafts
- Models com todos os relacionamentos definidos
- Factories para geração de dados de teste
- **Seeder completo e realista** (`DevelopmentSeeder`): cria 2 instituições, 3 contratos com cursos/períodos, 5+ pacotes com programações/condições/descontos/termos, configurações globais populadas — **este seeder é essencial para o desenvolvimento do portal sem admin**
- Seeder de admin user + perfis/permissões padrão
- Seeder de configurações globais com valores padrão

Validação do cliente: Modelo de dados completo. Ao rodar o seeder, o banco já tem dados realistas para testar o portal.

---

#### FASE 2 — PORTAL: ADESÃO (Sprints 4 a 9)

---

**Sprint 4 — Portal: Layout + Home + Código da Turma + Resumo**

Entregas:

- Layout completo do portal responsivo (mobile-first) com template escolhido (Preline UI ou Tailwind puro)
- Header, footer, navegação mobile (hamburger menu)
- Tela pública Home do Portal (landing page com input de código)
- Tela de Código da Turma: input com validação em tempo real, feedback de erro/sucesso
- Tela de Resumo da Turma: dados da instituição e contrato (nome, logo, cursos disponíveis)
- Middleware `portal.adesao.contrato` para validação e injeção de contexto
- Tabela `adesao_drafts` e lógica de persistência do wizard
- Services: `ContratoResolverService`, `AdesaoDraftService`

Validação do cliente: Portal acessível pelo celular, entrada por código funcional, visual agradável.

---

**Sprint 5 — Wizard Adesão: Etapas 1-3 (Curso/Período + Produtos)**

Entregas:

- Shell do wizard com barra de progresso visual (7 etapas) e navegação Voltar/Avançar
- Etapa 2 — Seleção de Curso/Período: selects dinâmicos baseados no contrato, gravação no draft
- Etapa 3 — Seleção de Pacotes/Produtos: cards com imagem, valor da programação vigente, categoria
- Lógica de grupo exclusivo (radio button para pacotes exclusivos, checkbox/quantidade para extras)
- Botão "Ver Termos" com PDF consolidado (variáveis em placeholder)
- Middleware `portal.adesao.state` para impedir pulo de etapas
- Services: `ProdutoDisponibilidadeService`, `ProgramacaoAtivaService`, `ProdutoGrupoExclusivoService`, `TermoPdfService`

Validação do cliente: Wizard navegável até a etapa de seleção de pacotes. Testar no celular.

---

**Sprint 6 — Wizard Adesão: Etapa 4 (Cadastro Completo)**

Entregas:

- Etapa 4a — Formulário do Formando: todos os campos com validações Livewire em tempo real, upload de foto, consulta CEP (ViaCEP)
- Etapa 4b — Responsável de Cadastro (condicional): checkbox "Eu sou o próprio", formulário completo
- Etapa 4c — Responsável Financeiro (condicional): checkbox, formulário, opção "mesmo do resp. cadastro"
- Etapa 4d — Conta do Portal: e-mail, senha, detecção de conta existente, autenticação e vinculação automática
- Verificação de CPF do formando em tempo real (uniqueness)
- Verificação de e-mail do portal_user em tempo real
- Services: `PortalUserResolverService`, `PortalUserVinculacaoService`, `PortalAuthFlowService`

Validação do cliente: Cadastro completo com todas as regras de responsáveis e detecção de duplicatas.

---

**Sprint 7 — Wizard Adesão: Etapa 5 (Pagamento com Cálculo Dinâmico)**

Entregas:

- Etapa 5 — Forma de Pagamento: seleção de modalidade (Boleto/Cartão/PIX)
- Se boleto: seleção do dia de vencimento dentre opções configuradas globalmente
- Cálculo dinâmico de parcelas em tempo real: redução por meses transcorridos, margem de emissão, ajuste fim de mês
- Aplicação automática de desconto por modalidade/faixa/vigência
- Exibição dinâmica: valor original, desconto, valor final, valor por parcela, datas de primeiro e último vencimento
- Se modalidade híbrida: detalhamento de parcelas em cada modalidade
- Services: `ParcelamentoCalculatorService`, `PrimeiroVencimentoService`, `CalendarioParcelasService`, `ModalidadeHibridaResolverService`, `ParcelaValorMinimoService`, `DescontoAplicavelService`, `CondicaoPagamentoDisponivelService`
- Testes unitários extensivos para o cálculo dinâmico (15+ cenários do PRD)

Validação do cliente: Cálculo dinâmico funcional. Testar cenários: adesão no início do período, no meio, próximo do vencimento. Trocar modalidade e ver valores mudando.

---

**Sprint 8 — Wizard Adesão: Etapa 6 (Conferência e Aceite de Termos)**

Entregas:

- Etapa 6 — Tela de conferência com resumo completo: dados pessoais, responsáveis, pacotes, valores, parcelas, datas
- Termos interpolados com dados reais do formando (variáveis substituídas)
- Download de PDF consolidado com variáveis preenchidas
- Checkboxes de aceite obrigatório por pacote
- Registro de IP, user_agent e timestamp do aceite
- Service: `AdesaoSnapshotService`, `TermoInterpolatorService`, `TermoConsolidatorService`, `AceiteTermoRecorderService`

Validação do cliente: Tela de conferência com todos os dados corretos, termos preenchidos, PDF funcional.

---

**Sprint 9 — Wizard Adesão: Etapa 7 (Checkout + Finalização) com Gateway Mock**

Entregas:

- Etapa 7 — Checkout: criação da adesão, adesao_produtos (com snapshots), aceites_termos (com snapshot HTML)
- Geração das parcelas com todas as regras (vencimento, ajuste fim de mês, híbrida)
- **Gateway Mock** (`GatewayMockService`): simula processamento de Boleto, PIX e Cartão para testes
- Tela de Sucesso com resumo e botão para área do formando
- Tela de Falha com opção de retentativa ou troca de modalidade
- E-mail de boas-vindas (Job assíncrono) — template básico
- Services: `AdesaoCheckoutService`, `AdesaoFinalizeService`, `CalendarioParcelasService`

Validação do cliente: **Fluxo completo de adesão funcional do início ao fim (com gateway simulado).** Fazer uma adesão de teste completa e verificar todos os dados persistidos.

---

#### FASE 3 — PORTAL: ÁREA DO FORMANDO (Sprints 10 a 11)

---

**Sprint 10 — Auth Portal + Multi-Formando + Dashboard + Extrato**

Entregas:

- Login do portal (e-mail + senha, guard portal) com feedback visual
- Recuperação de senha (e-mail com link + tela de redefinição)
- Seletor de multi-formando (quando 2+ formandos vinculados ao portal_user)
- Middleware `portal.formando.context` para isolamento de contexto
- Dashboard do formando: status adimplência (card), próxima parcela (card), pacotes resumo (cards)
- Botão "Trocar Formando" no header do portal
- Extrato Financeiro por Pacote: listagem de parcelas, totalizadores, download de boleto (mock), código PIX (mock)
- Detalhes do pacote com termos aceitos e reajustes aplicados

Validação do cliente: Login funcional, multi-formando navegável, dashboard e extrato completos. Testar com responsável que tem 2 formandos.

---

**Sprint 11 — Área do Formando: Dados, Extras e Senha**

Entregas:

- Dados Cadastrais: visualização de todos os dados + edição limitada (CPF somente leitura)
- Dados dos responsáveis (somente visualização)
- Troca de Senha do portal_user
- Catálogo de Extras: listagem de produtos extras disponíveis no contrato (imagem, valor, termos)
- Checkout de Extras: fluxo simplificado (selecionar → quantidade → pagamento com cálculo dinâmico → aceitar termos → checkout com gateway mock)
- Logout

Validação do cliente: **Portal do Formando MVP completo — todas as funcionalidades operacionais com gateway mock.** Testar: adesão, login, extrato, compra de extra, troca de senha, multi-formando.

---

#### FASE 4 — INTEGRAÇÃO GATEWAY ITAÚ (Sprints 12 a 13)

---

**Sprint 12 — Integração Itaú: Boleto Bancário**

Entregas:

- `ItauGatewayService` (service base com autenticação OAuth2, retry com backoff, error handling)
- `ItauBoletoService`: geração de boleto registrado, download PDF, linha digitável, nosso número
- `WebhookPagamentoService`: recebimento do webhook, validação de assinatura HMAC, processamento
- Controller: `ItauWebhookController@boleto`
- Atualização automática de status das parcelas via webhook (pendente → pago)
- E-mail automático com boleto em anexo
- Reemissão de boleto (funcional, porém o admin ainda não existe — disponível via tinker ou futuro admin)
- Substituição do mock por integração real no fluxo de adesão e área do formando

Validação do cliente: Fazer adesão com boleto real (ambiente homologação Itaú). Pagar boleto e ver status atualizar. Verificar e-mail.

---

**Sprint 13 — Integração Itaú: PIX + Cartão + Híbrida**

Entregas:

- `ItauPixService`: geração de cobrança dinâmica, QR Code, copia-e-cola
- Webhook PIX: confirmação automática
- `ItauCartaoService`: processamento de pagamento, tokenização para recorrência
- `PagamentoRetryService`: retentativa com backoff (máx 3 tentativas)
- Modalidade híbrida: `ModalidadeHibridaResolverService` define modalidade efetiva por parcela com base na data limite
- Estorno de cartão (funcionalidade base, ação disponível futuramente no admin)

Validação do cliente: Fazer adesão com PIX (QR Code real, confirmação automática) e com Cartão. Testar modalidade híbrida. **Gateway totalmente integrado.**

---

#### FASE 5 — E-MAILS + REFINAMENTOS DO PORTAL (Sprint 14)

---

**Sprint 14 — E-mails Transacionais + Automações + Refinamentos**

Entregas:

- Todos os templates de e-mail: adesão concluída, boleto gerado, pagamento confirmado, lembrete de vencimento, parcela vencida, boleto reemitido, reajuste aplicado, recuperação de senha
- `SendPaymentReminderJob` (scheduler diário): envia lembretes baseados em `email_lembrete_dias_antes`
- `MarkOverdueParcelasJob` (scheduler diário 00:05): marca parcelas vencidas como `vencido`
- `CleanExpiredDraftsJob` (scheduler diário 03:00): limpa drafts expirados
- Refinamentos de UX do portal identificados nas sprints 4-13
- Edge cases: formando com programação expirada, formando sem parcelas, formatação monetária, responsivo em devices específicos

Validação do cliente: Verificar todos os e-mails (design e conteúdo). Simular parcela vencida e ver lembrete/notificação. **Portal totalmente funcional e integrado — pronto para produção.**

---

#### FASE 6 — ADMIN: CORE (Sprints 15 a 19)

---

**Sprint 15 — Auth Admin + ACL + Layout Inspinia + Navegação**

Entregas:

- Tela de Login Admin (Inspinia) com validação, throttle e feedback visual
- Middleware de admin ativo (`admin.active`) e middleware de permissão ACL (`can.permission`)
- Layout admin completo: sidebar colapsável com menu completo, header com breadcrumb, dropdown de perfil, toggle tema claro/escuro
- CRUD de Perfis ACL com tela de matriz de permissões (checkboxes por módulo/ação)
- CRUD de Usuários Admin (listagem, criação, edição, inativação, reset de senha)
- Shell do Dashboard (layout com KPIs em branco, a ser preenchido nas sprints futuras)

Validação do cliente: Login admin funcional, menu navegável, gestão de usuários e perfis operacional.

---

**Sprint 16 — CRUD Instituições + CRUD Contratos (Dados + Responsáveis)**

Entregas:

- CRUD completo de Instituições (DataTable com busca/filtros, formulário com upload logo, máscara CNPJ, consulta CEP)
- CRUD de Contratos — Tab 1 (Dados do Contrato): todos os campos, select searchable de instituição
- CRUD de Contratos — Tab 2 (Configurações de Responsáveis): toggles com preview dinâmico
- Listagem de contratos com DataTable, filtros por instituição/status/conclusão e ações (editar, duplicar)
- Funcionalidade de duplicação de contrato

Validação do cliente: Cadastro de instituições e contratos funcional com todas as validações.

---

**Sprint 17 — Contratos: Cursos, Períodos, Reajustes + Categorias + Índices**

Entregas:

- Tab 3 do contrato — Cursos: CRUD inline com adição/remoção dinâmica
- Tab 4 do contrato — Períodos: CRUD inline
- Tab 5 do contrato — Reajustes: listagem, cadastro (modal), botão de aplicação com confirmação
- CRUD de Índices de Reajuste (tabela auxiliar global, modal Inspinia)
- CRUD de Categorias de Produto (modal/drawer Inspinia)
- Lógica de aplicação de reajuste em parcelas: `ContratoReajusteService` + `ApplyContratoReajustesJob`

Validação do cliente: Contratos com cursos, períodos e reajustes configuráveis. Categorias e índices cadastráveis.

---

**Sprint 18 — CRUD Pacotes/Produtos + Programações de Valor**

Entregas:

- CRUD de Pacotes/Produtos — Tab 1 (Dados): todos os campos, upload imagem, select contrato/categoria
- Listagem de produtos com DataTable e filtros (contrato, categoria, status, disponibilidade)
- Menu de ações dropdown por produto
- Subtela de Programações (Tab 2): listagem em timeline, modal de criação/edição, indicadores visuais (ativa/futura/expirada/gaps)
- Validação de sobreposição em tempo real (frontend + backend)
- Preview: "Se formando aderir em [data], paga R$ [X] em [N]x"
- Service: `ProgramacaoAtivaService` (já existente, agora com tela admin)

Validação do cliente: Cadastro de produtos e programações funcional com validação de sobreposição.

---

**Sprint 19 — Condições de Pagamento + Descontos + Termos (Admin)**

Entregas:

- Subtela de Condições de Pagamento (Tab 3): listagem, modal, config de híbrida
- Subtela de Descontos (Tab 4): listagem, modal, validação de sobreposição
- CRUD de Termos (listagem, formulário full com TinyMCE, barra de variáveis clicáveis, controle de versão)
- Preview de termo com dados de exemplo
- Subtela de vinculação termos ↔ produtos (Tab 5): drag-and-drop para ordenação
- Preview de PDF consolidado dos termos do produto
- Botão "Ver Preview no Portal" que simula a visualização do formando

Validação do cliente: Configuração completa de condições de pagamento, descontos e termos. **CRUDs base do admin completos.**

---

#### FASE 7 — ADMIN: FINANCEIRO E GESTÃO (Sprints 20 a 23)

---

**Sprint 20 — Gestão de Formandos (Admin) + Ficha Completa**

Entregas:

- Listagem de Formandos com DataTable e filtros avançados (contrato, curso, período, adimplência, data, pacote)
- Ficha completa do formando: sidebar com foto/dados-chave + tabs
- Tab 1 — Dados Pessoais (visualização + edição via modal)
- Tab 2 — Responsáveis (visualização)
- Tab 3 — Portal Users Vinculados (tabela + vincular/desvincular)
- Tab 4 — Pacotes e Adesão (detalhes com snapshots de programação, valor, desconto)
- Service: `FormandoContextService`

Validação do cliente: Ficha do formando funcional com todas as tabs de dados.

---

**Sprint 21 — Extrato Financeiro (Admin) + Ações sobre Parcelas**

Entregas:

- Tab 5 da ficha — Extrato Financeiro: tabela de parcelas com totalizadores (total geral, pago, pendente, vencido)
- Ações por parcela: baixa manual (modal com valor/data/observação), reemissão de boleto (nova data → chama gateway), cancelamento (modal com motivo), alteração de valor (modal com justificativa)
- Todas as ações com registro de log de auditoria
- Tab 6 — Termos Aceitos (visualização com snapshot)
- Tab 7 — Histórico/Auditoria (timeline de alterações)
- Services: `ExtratoFinanceiroService`, `ParcelaStatusUpdaterService`, `CobrancaReemissaoService`

Validação do cliente: Gestão financeira do formando funcional. Testar todas as ações sobre parcelas.

---

**Sprint 22 — Parcelas Consolidada + Dashboard Admin**

Entregas:

- Tela de Parcelas Consolidada (`/admin/financeiro/parcelas`): DataTable com filtros avançados, KPIs (total, recebido, a receber, vencidas), ações em lote (baixa, exportação)
- Exportação CSV/Excel com filtros aplicados
- Preenchimento do Dashboard Admin com dados reais: KPI cards (contratos, formandos, receita, inadimplência), gráficos de adesões e receita (ApexCharts), tabela meta x aderidos, últimas adesões, parcelas vencendo
- Alertas do sistema no dashboard (programações expirando, parcelas sem tratamento, reajustes pendentes)

Validação do cliente: Dashboard com dados reais e tela financeira consolidada funcional.

---

**Sprint 23 — Simulador + Relatórios + Cadastro Manual + Configurações**

Entregas:

- Simulador de Parcelamento: campos (contrato, produto, data simulada, modalidade, dia vencimento, parcelas), resultado em tempo real com cronograma de parcelas
- Tela de Relatórios com 6 relatórios (Adesões, Receita, Inadimplência, Recebimentos, Formandos por Curso, Reajustes)
- Exportação CSV/Excel em cada relatório
- Cadastro Manual de Formando pelo admin (formulário compacto com todas as seções em accordion)
- Tela de Configurações Globais: cards agrupados, inputs especializados (tags input, toggles, monetário), preview dinâmico

Validação do cliente: **Admin MVP completo — todas as telas administrativas funcionais.**

---

#### FASE 8 — ADMIN: FINALIZAÇÃO (Sprint 24)

---

**Sprint 24 — Usuários Admin + Perfis ACL + Logs de Auditoria**

Entregas:

- Refinamento da gestão de usuários admin e perfis (já criados na Sprint 15, agora com polimento completo)
- Tela de Logs de Auditoria: DataTable com filtros por módulo, ator, ação, período, entidade
- Middleware `audit.request` para captura automática de contexto
- Observers para Models com auditoria automática (ParcelaObserver, AdesaoObserver, FormandoObserver, ConfiguracaoGlobalObserver)
- Refinamentos gerais do admin identificados nas sprints 15-23
- Notificações no header admin (ícone sino com badge)

Validação do cliente: Admin completo com auditoria e refinamentos.

---

#### FASE 9 — HOMOLOGAÇÃO E IMPLANTAÇÃO (Sprints 25 a 26)

---

**Sprint 25 — Homologação + Testes Integrados**

Entregas:

- Testes completos em ambiente de homologação com dados reais/realistas
- Teste do fluxo completo: criar contrato no admin → configurar pacotes → formando adere no portal → pagamento → verificar no admin
- Testes de carga básicos (múltiplas adesões simultâneas)
- Validação de todos os e-mails em cenários reais
- Validação de webhooks em produção-like
- Correção de bugs identificados
- Ajustes de UX e textos

Validação do cliente: Testar o sistema como se estivesse em produção. Aprovar para go-live.

---

**Sprint 26 — Ajustes Finais + Implantação**

Entregas:

- Correções de bugs da homologação
- Deploy em ambiente de produção
- Migração de dados (se houver)
- Treinamento da equipe administrativa (2-3 sessões)
- Documentação de uso para o cliente
- Monitoramento da primeira semana de operação

Validação do cliente: **Go-Live. Sistema em produção.**

---

### 20.4 Marcos do Projeto

| Marco                             | Sprint        | Descrição                                                      |
| --------------------------------- | ------------- | -------------------------------------------------------------- |
| Modelo de dados completo          | Sprint 3      | Todas as tabelas e seeders realistas prontos                   |
| Wizard de adesão funcional (mock) | Sprint 9      | Fluxo completo de 7 etapas operando com gateway simulado       |
| Portal MVP completo (mock)        | Sprint 11     | Adesão + Área do formando + Extras — tudo funcional            |
| Gateway Itaú integrado            | Sprint 13     | Boleto, PIX, Cartão reais em homologação                       |
| **Portal pronto para produção**   | **Sprint 14** | Portal completo com gateway real + e-mails + automações        |
| Admin CRUDs completos             | Sprint 19     | Instituições, contratos, produtos, termos — todos operacionais |
| **Admin MVP completo**            | **Sprint 23** | Todas as telas do admin funcionais                             |
| Sistema homologado                | Sprint 25     | Testes finais aprovados                                        |
| **Go-Live**                       | **Sprint 26** | Sistema em produção                                            |

### 20.5 Dependências Críticas

- **Credenciais de integração Itaú** — necessárias até a Sprint 12. Sem elas, as Sprints 12-13 ficam bloqueadas (gateway mock continua funcionando, mas integração real não avança).
- **Ambiente de homologação Itaú** — necessário até a Sprint 12.
- **Aprovação dos requisitos e regras de negócio** — bloqueante para Sprint 4. Após aprovação, mudanças de escopo vão para backlog futuro.
- **Definição da identidade visual do portal** — antes da Sprint 4 (cores, logo, preferências de estilo).
- **Definição de políticas** (cancelamento, reembolso, inadimplência) — antes da Sprint 9.
- **Textos dos termos de adesão** — aprovação antes da Sprint 8.
- **Servidor de produção configurado** — antes da Sprint 25.

### 20.6 Riscos

| Risco                                | Probabilidade | Impacto | Mitigação                                                                              |
| ------------------------------------ | :-----------: | :-----: | -------------------------------------------------------------------------------------- |
| Atraso nas credenciais Itaú          |     Alta      |  Alto   | Gateway mock permite portal funcional sem integração real; contato antecipado com Itaú |
| Mudanças de escopo                   |     Média     |  Alto   | Backlog congelado após Sprint 3, changes via backlog futuro                            |
| Complexidade do cálculo dinâmico     |     Média     |  Médio  | Testes unitários extensivos na Sprint 7, simulador na Sprint 23                        |
| Complexidade das sobreposições       |     Média     |  Médio  | Validações em tempo real com feedback visual claro                                     |
| Portal sem admin para dados iniciais |     Baixa     |  Baixo  | Seeders robustos + tinker para ajustes durante sprints 4-14                            |
| Bugs críticos em produção            |     Baixa     |  Alto   | Homologação dedicada (Sprint 25), monitoramento pós-deploy                             |
| Performance com muitos formandos     |     Baixa     |  Médio  | Índices no banco, cache Redis, queries otimizadas                                      |

---

## 21. Funcionalidades Futuras (Backlog)

_(Mantém-se conforme PRD v2.1 — Seção 21: QR Code, Validação de Convites, Mesas Online, WhatsApp, App Mobile.)_

---

## 22. Glossário

_(Mantém-se conforme PRD v2.1 — Seção 22.)_

---

## 23. Controle de Versões

| Versão    | Data           | Autor        | Descrição                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| --------- | -------------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.0.0     | 11/02/2026     | Leonardo     | Versão inicial                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| 1.1.0     | 11/02/2026     | Leonardo     | Acesso Multi-Formando                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| 1.2.0     | 11/02/2026     | Leonardo     | Imagem em pacotes, foto do formando, ACL                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| 1.3.0     | 11/02/2026     | Leonardo     | Sistema de Termos de Adesão com variáveis dinâmicas                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| 1.3.1     | 11/02/2026     | Leonardo     | Cronograma otimizado                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| 2.0.0     | 26/02/2026     | Leonardo     | Reescrita completa do PRD com modelagem detalhada                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| 2.1.0     | 26/02/2026     | Leonardo     | Programações, cálculo dinâmico, config globais, portal_users                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **3.0.0** | **17/03/2026** | **Leonardo** | **Reestruturação Portal-First:** Toda a ordem de desenvolvimento foi invertida — Portal do Formando (adesão + área autenticada) é construído ANTES do Backoffice Administrativo. Sprints de 7 dias (26 sprints) com fases claras: Fundação (1-3), Portal Adesão (4-9), Portal Área (10-11), Gateway Itaú (12-13), E-mails (14), Admin Core (15-19), Admin Financeiro (20-23), Admin Finalização (24), Homologação (25-26). Seeders robustos para alimentar portal durante desenvolvimento. Detalhamento completo de 20+ telas do admin (Metronic/Tailwind). Recomendação de template portal (Preline UI). Cadastro manual de formando. Gestão de admin users + ACL. Logs de auditoria. Tabelas adicionais (adesao_drafts, audit_logs, pagamentos, pagamento_eventos). |
| **3.1.0** | **09/04/2026** | **Leonardo** | **Atualização de Stack e Templates:** Laravel 12 → **Laravel 13** (PHP 8.4). Template admin Metronic substituído por **Inspinia Multipurpose Admin Dashboard (Tailwind CSS 4)**. Todas as referências a componentes Metronic atualizadas para Inspinia. Template do portal: decisão entre Preline UI e Tailwind puro (pendente Sprint 4). Seção 19 reestruturada com 19.1 (Portal) e 19.2 (Admin). Plugins referenciados atualizados: Select2 → Choices.js, Datepicker genérico → Flatpickr, Tags genérico → Tagify. Documentação do projeto estruturada em `.docs/` com guias de arquitetura, convenções, padronização e gestão via Linear.                                                                                                                          |

---

**Documento elaborado por:**  
Leonardo — HT2ML TECH LTDA  
Senior Systems Analyst & Developer
