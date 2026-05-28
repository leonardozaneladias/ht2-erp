# Módulo: Adesão Wizard (Portal)

**Sprint:** 4-9
**Última Atualização:** —
**Status:** 🔴 Pendente
**Referência PRD:** Seção 12 (Fluxo de Adesão do Formando)

## Escopo

_A definir durante a implementação._ Wizard de 7 etapas do portal: código → curso/período → pacotes → cadastro → pagamento → conferência → checkout. Persistência de drafts, validação por etapa, consolidação de termos em PDF.

## Models Envolvidos

| Model | Tabela | Campos Principais |
| ----- | ------ | ----------------- |
| —     | —      | —                 |

## Services e Actions

| Classe                       | Método Principal | Responsabilidade                    |
| ---------------------------- | ---------------- | ----------------------------------- |
| CreateAdesaoFromWizardAction | execute()        | Finaliza o wizard e persiste adesão |
| —                            | —                | —                                   |

## Rotas

| Método | URI | Controller@Action | Middleware |
| ------ | --- | ----------------- | ---------- |
| —      | —   | —                 | —          |

## Components Blade Utilizados

- `x-portal.wizard-progress`
- `x-portal.step-card`
- —

## Regras de Negócio

1. Drafts expiram após N dias (configurável)
2. Cada etapa valida via FormRequest antes de avançar
3. Snapshot imutável dos dados comerciais no momento da adesão
4. A definir

## Telas / UI

—

## Testes

| Teste | Tipo | Cenário |
| ----- | ---- | ------- |
| —     | —    | —       |

Cobertura crítica: fluxo end-to-end do wizard (ver `CONVENTIONS.md §6.3`).

## Dependências

- Depende de: Produtos/Pacotes (05), Cálculo de Parcelas (10), Termos (— a definir)
- Dependido por: Gateway de Pagamentos (16), Área do Formando (12)

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
