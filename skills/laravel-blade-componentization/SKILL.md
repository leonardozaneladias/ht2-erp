---
name: laravel-blade-componentization
description: Implementa componentes Blade em projetos Laravel a partir de documentação existente, catálogo de componentes e mapa tela→componentes, priorizando reuso, composição, consistência de API, dark mode, responsividade e atualização das docs.
---

# Laravel Blade Componentization

## Objetivo

Esta skill existe para implementar componentes Blade reais em um projeto Laravel que já possui:

- inventário do template
- documentação detalhada por componente
- catálogo de componentes
- mapa tela→componentes
- regras arquiteturais no projeto

O foco desta skill é **executar a componentização com disciplina**, e não reabrir análise já concluída.

---

## Quando usar

Use esta skill quando a tarefa for:

- criar componentes Blade reais a partir de documentação já pronta
- consolidar API de componentes antes de implementá-los
- criar previews visuais de componentes
- atualizar documentação após implementação
- transformar componentes documentados em código utilizável no projeto

---

## Fontes oficiais obrigatórias

Antes de implementar qualquer componente, consultar obrigatoriamente:

- `docs/INSPINIA-CATALOGO-COMPONENTES.md`
- `docs/INSPINIA-MAPA-TELAS-COMPONENTES.md`
- `docs/04-TEMPLATE-MAP-AND-COMPONENTS.md`
- `docs/template/INSPINIA/**/*.md`
- `CLAUDE.md`
- `docs/02-CONVENTIONS.md`

Estas fontes têm precedência sobre qualquer inferência.

---

## Regras principais

### 1. Não recomeçar fases concluídas

Não refazer inventário, triagem, catálogo ou mapa se já existirem e estiverem atualizados.

### 2. Implementar em batches pequenos

Nunca componentizar tudo de uma vez.
Trabalhar em lotes controlados.

### 3. Consolidar API antes de implementar

Antes de criar um componente, validar:

- nome final
- namespace final
- props finais
- slots finais
- dependências JS/CSS
- tipo final:
    - Blade anônimo
    - class-based
    - Livewire

### 4. Ordem de preferência arquitetural

Sempre preferir nesta ordem:

1. reuso
2. composição
3. variação via props
4. novo componente

### 5. Não transformar páginas completas em componentes

Views e páginas completas devem continuar sendo views, exceto quando a documentação já indicar claramente outra estratégia.

### 6. Atualizar documentação após implementar

Toda implementação concluída deve atualizar:

- doc detalhada do componente
- catálogo de componentes
- status do componente

### 7. Criar preview visual

Todo componente implementado deve ter preview em ambiente de desenvolvimento.

---

## Regras de implementação Blade

### Preferência de tipo

- Preferir Blade anônimo quando possível
- Usar class-based apenas se houver lógica PHP real
- Usar Livewire apenas se houver interação com servidor necessária

### Consistência visual

Todo componente deve:

- suportar dark mode quando aplicável
- ser responsivo quando aplicável
- evitar Bootstrap residual
- evitar jQuery
- explicitar dependências JS/plugin quando existirem

### API de componente

A API deve ser:

- previsível
- pequena
- coerente com os componentes já existentes
- orientada a props claras e slots úteis
- sem excesso de flags booleanas desnecessárias

---

## Estrutura esperada dos arquivos

### Componentes

- `resources/views/components/shared/...`
- `resources/views/components/admin/...`
- `resources/views/components/portal/...`

### Previews

- `resources/views/admin/dev/components/[nome].blade.php`

### Docs

- `docs/template/INSPINIA/...`
- `docs/INSPINIA-CATALOGO-COMPONENTES.md`

---

## Processo obrigatório por componente

Para cada componente:

### Etapa 1 — Ler a documentação

Ler a doc detalhada correspondente e localizar:

- código original
- proposta Blade
- props
- slots
- dependências
- exemplos

### Etapa 2 — Consolidar API

Confirmar ou ajustar:

- nome
- namespace
- props
- slots
- subcomponentes necessários
- dependências reais

Se houver inconsistência entre docs e catálogo, corrigir antes de implementar.

### Etapa 3 — Implementar

Criar o componente real no local apropriado.

### Etapa 4 — Criar preview

Criar preview visual com:

- exemplo básico
- variantes principais
- exemplo próximo ao domínio do projeto, quando fizer sentido

### Etapa 5 — Atualizar docs

Atualizar a doc detalhada com:

- status
- seção "Código Final Blade"
- observações de implementação

### Etapa 6 — Atualizar catálogo

Atualizar status do componente no catálogo.

---

## Critério de pronto

Um componente só pode ser marcado como pronto se:

- foi implementado
- sua API foi consolidada
- possui preview visual
- a doc foi atualizada
- o catálogo foi atualizado
- não há conflito evidente com outro componente existente

Se não estiver maduro:

- não forçar
- marcar como parcial/pendente
- registrar motivo

---

## O que evitar

- reabrir decisões já tomadas
- criar componente novo quando composição resolve
- criar páginas inteiras como componentes
- propagar inconsistência de nomenclatura
- deixar dependência JS implícita
- ignorar atualização das docs
- ignorar catálogo

---

## Exemplos de pedidos adequados

- "Implemente o Batch 1 da Fase 6 usando o catálogo atual"
- "Consolide a API e implemente x-shared.button"
- "Implemente os componentes base e crie previews"
- "Atualize docs e catálogo após criar os componentes"

---

## Saída esperada

Ao concluir um lote, reportar:

- componentes concluídos
- componentes parcialmente concluídos
- componentes adiados
- arquivos criados/alterados
- inconsistências corrigidas
- bloqueios encontrados
- próximo lote recomendado
