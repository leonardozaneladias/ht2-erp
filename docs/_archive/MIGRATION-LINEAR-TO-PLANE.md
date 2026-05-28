# Migração Linear → Plane

**Data:** 10/04/2026  
**Contexto:** Sprint 0 iniciada no Linear. Migração para Plane Cloud (free) por limitação de 250 issues e necessidade de acesso do cliente como Guest.

---

## 1. Por Que Migrar

| Problema no Linear (free)                    | Solução no Plane (free)                              |
| -------------------------------------------- | ---------------------------------------------------- |
| 250 issues máx (bloqueia criação ao atingir) | Issues ilimitadas                                    |
| Sem acesso de Guest/Cliente no free          | Guest gratuito (5 por seat pago, ilimitados no free) |
| $8/user/mês se precisar crescer              | $6/seat/mês, guests não pagam                        |
| Cloud only                                   | Cloud + self-hosted                                  |

---

## 2. O Que É Migrado Automaticamente

O Plane tem importador nativo do Linear. O que transfere:

| Dado Linear                                    | → Plane                            |
| ---------------------------------------------- | ---------------------------------- |
| Issues (título, descrição, status, prioridade) | ✅ Work Items                      |
| Labels                                         | ✅ Labels                          |
| Cycles                                         | ✅ Cycles                          |
| Projects                                       | ✅ Modules                         |
| Comments e attachments                         | ✅ Preservados                     |
| Users e assignees                              | ✅ Mapeados                        |
| States (Backlog, Todo, In Progress, Done)      | ✅ Mapeamento manual na importação |

---

## 3. Passo a Passo da Migração

### 3.1 Preparar no Linear

1. Ir em **Linear → Settings → Account → Security & Access**
2. Em **Personal API Keys**, criar uma nova API Key
3. Copiar e guardar o token (começa com `lin_api_...`)

### 3.2 Criar Workspace no Plane

1. Acessar [app.plane.so](https://app.plane.so) e criar conta
2. Nome do Workspace: **HT2ML TECH**
3. Criar um Projeto: **Portal ArtFinal**

### 3.3 Executar a Importação

1. Ir em **Plane → Workspace Settings → Imports**
2. Selecionar **Linear Importer**
3. Colar o Personal Access Token do Linear
4. Clicar **Connect Linear**
5. Selecionar o projeto Plane destino: **Portal ArtFinal**
6. Selecionar o team Linear de origem: **Portal ArtFinal (PAF)**
7. **Mapear states:**

| Linear State | → Plane State                 |
| ------------ | ----------------------------- |
| Backlog      | Backlog                       |
| Todo         | Todo                          |
| In Progress  | In Progress                   |
| In Review    | In Progress (ou criar custom) |
| Done         | Done                          |
| Cancelled    | Cancelled                     |

8. Revisar o summary e clicar **Confirm**
9. Aguardar a migração (poucos minutos para Sprint 0)
10. Verificar em **Work Items** se tudo chegou corretamente

### 3.4 Pós-Migração

1. **Recriar labels** se não vieram automaticamente (ver seção de labels no PLANE-GUIDE.md)
2. **Convidar o cliente** como Guest: Workspace Settings → Members → Add Guest
3. **Configurar Cycles** para 1 semana
4. **Conectar GitHub**: Workspace Settings → Integrations → GitHub
5. **Configurar MCP** do Plane no Claude Code (ver PLANE-GUIDE.md)

### 3.5 Desativar o Linear

Após confirmar que tudo está no Plane:

1. Manter o Linear acessível como referência (não deletar dados)
2. Remover o MCP do Linear do Claude Code:
    ```bash
    claude mcp remove linear-server
    ```
3. Não criar novas issues no Linear — tudo passa a ser no Plane
4. Atualizar o CLAUDE.md na raiz do projeto (trocar "Linear" por "Plane")

---

## 4. Tempo Estimado

| Etapa                       | Tempo       |
| --------------------------- | ----------- |
| Criar API Key no Linear     | 2 min       |
| Criar workspace no Plane    | 5 min       |
| Executar importação         | 5 min       |
| Verificar e ajustar         | 10 min      |
| Configurar GitHub + MCP     | 10 min      |
| Convidar cliente como Guest | 5 min       |
| **Total**                   | **~40 min** |

---

## 5. Referências

- [Plane: Import from Linear](https://docs.plane.so/importers/linear)
- [Plane Marketplace: Linear Importer](https://plane.so/marketplace/linear)
