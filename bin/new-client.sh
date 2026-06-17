#!/usr/bin/env bash
# new-client.sh — instancia um CLIENTE do HT2 ERP de forma ADITIVA.
#
# Diferente de init-project.sh (que deriva um PRODUTO NOVO e corta o git history),
# este script PRESERVA o histórico para que o cliente continue recebendo updates da
# base via `make update-base` (git merge upstream). Ver ADR-0016 e
# docs/distribuicao-manutencao.md.
#
# Rode-o DENTRO de um clone fresco da base:
#   git clone git@github.com:leonardozaneladias/ht2-erp.git cliente-gdf && cd cliente-gdf
#   ./bin/new-client.sh
#
# O que faz (tudo aditivo / local — nada some do git da base):
#   - Remotes: garante origin=cliente, upstream=base (idempotente).
#   - .env: cria a partir de .env.example (se faltar) e ajusta APP_NAME, APP_URL,
#           HORIZON_PREFIX, MAIL_FROM_ADDRESS.
#   - .ddev/config.local.yaml: name=<slug> (override local do DDEV; gitignored).
#   - .husky/allow-main-push: opt-out local do bloqueio de push à main (gitignored).
#
# O que NÃO faz: não toca composer.json, não apaga git, não renomeia títulos
# versionados (branding é runtime, via Setup Wizard em /admin/setup).
#
# Uso:
#   ./bin/new-client.sh           # interativo
#   ./bin/new-client.sh --dry-run # mostra o que mudaria sem editar

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}"

DRY_RUN=false
if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
    echo -e "${YELLOW}=== Modo dry-run: nenhuma mudança será aplicada ===${NC}"
fi

echo -e "${GREEN}👤 Instanciando um novo CLIENTE a partir da base HT2 ERP${NC}"
echo ""

# sed portável (macOS BSD e GNU)
sed_inplace() {
    if sed --version >/dev/null 2>&1; then
        sed -i "$@"
    else
        sed -i '' "$@"
    fi
}

apply() {
    local descricao="$1"
    shift
    if $DRY_RUN; then
        echo -e "  ${YELLOW}[dry-run]${NC} ${descricao}"
    else
        "$@"
        echo -e "  ${GREEN}✓${NC} ${descricao}"
    fi
}

# --- Estado dos remotes (suporta re-origin já feito OU clone fresco) ---
HAS_UPSTREAM=false
git remote | grep -qx upstream && HAS_UPSTREAM=true
CURRENT_ORIGIN="$(git remote get-url origin 2>/dev/null || echo '')"

# --- Coleta de dados ---
if $DRY_RUN; then
    APP_NAME="Grupo ACME"
    SLUG="acme"
    MAIL_DOMAIN="acme.com.br"
    CLIENT_REPO_URL="git@github.com:leonardozaneladias/ht2-erp-acme.git"
    echo -e "${YELLOW}(valores fictícios em dry-run: APP_NAME='${APP_NAME}', SLUG='${SLUG}')${NC}"
else
    read -p "Nome do cliente (ex: 'Grupo GDF'): " APP_NAME
    read -p "Slug (minúsculas, sem espaços — ex: 'gdf'): " SLUG
    read -p "Domínio de e-mail (ex: 'gdf.com.br'): " MAIL_DOMAIN

    if [[ -z "${APP_NAME}" || -z "${SLUG}" || -z "${MAIL_DOMAIN}" ]]; then
        echo -e "${RED}Abortado: nome, slug e domínio são obrigatórios.${NC}"
        exit 1
    fi
    if [[ ! "${SLUG}" =~ ^[a-z][a-z0-9-]*$ ]]; then
        echo -e "${RED}Slug inválido. Use apenas letras minúsculas, números e hífen.${NC}"
        exit 1
    fi

    # Só precisamos da URL do cliente se o re-origin ainda não foi feito.
    CLIENT_REPO_URL=""
    if ! $HAS_UPSTREAM; then
        echo ""
        echo -e "${YELLOW}upstream não configurado — assumindo clone fresco (origin = base).${NC}"
        read -p "URL do repo do CLIENTE (origin, ex: git@github.com:leonardozaneladias/ht2-erp-gdf.git): " CLIENT_REPO_URL
        if [[ -z "${CLIENT_REPO_URL}" ]]; then
            echo -e "${RED}Abortado: a URL do repo do cliente é obrigatória num clone fresco.${NC}"
            exit 1
        fi
    fi
fi

# --- 1. Remotes (origin=cliente, upstream=base) ---
echo ""
echo -e "${GREEN}— Remotes —${NC}"
if $HAS_UPSTREAM; then
    echo -e "  ${YELLOW}!${NC} upstream já existe ($(git remote get-url upstream 2>/dev/null)). Re-origin já feito; mantendo."
    echo -e "  ${YELLOW}!${NC} origin = ${CURRENT_ORIGIN:-<nenhum>}"
else
    if [[ -n "${CURRENT_ORIGIN}" ]]; then
        apply "upstream → base (${CURRENT_ORIGIN})" git remote rename origin upstream
    fi
    apply "origin → cliente (${CLIENT_REPO_URL})" git remote add origin "${CLIENT_REPO_URL}"
fi

# --- 2. .env (gitignored) ---
echo ""
echo -e "${GREEN}— .env —${NC}"
if [[ ! -f .env ]]; then
    apply ".env (cópia de .env.example)" cp .env.example .env
fi
if $DRY_RUN || [[ -f .env ]]; then
    apply ".env → APP_NAME, APP_URL, HORIZON_PREFIX, MAIL" \
        bash -c "
        sed_inplace_fn() { if sed --version >/dev/null 2>&1; then sed -i \"\$@\"; else sed -i '' \"\$@\"; fi; }
        sed_inplace_fn 's|APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|' .env
        sed_inplace_fn 's|APP_URL=.*|APP_URL=https://${SLUG}.ddev.site|' .env
        sed_inplace_fn 's|HORIZON_PREFIX=.*|HORIZON_PREFIX=${SLUG}_horizon:|' .env
        sed_inplace_fn 's|MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"noreply@${MAIL_DOMAIN}\"|' .env
        "
fi

# --- 3. .ddev/config.local.yaml (override local; gitignored) ---
echo ""
echo -e "${GREEN}— DDEV (override local) —${NC}"
if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run]${NC} .ddev/config.local.yaml → name: ${SLUG}"
else
    cat > .ddev/config.local.yaml <<EOF
# Override local desta INSTÂNCIA (gitignored por .ddev/.gitignore).
# Define a URL https://${SLUG}.ddev.site sem colidir com .ddev/config.yaml (versionado).
name: ${SLUG}
EOF
    echo -e "  ${GREEN}✓${NC} .ddev/config.local.yaml → name: ${SLUG}"
fi

# --- 4. Opt-out local do pre-push (1 dev empurra direto no repo do cliente) ---
echo ""
echo -e "${GREEN}— Husky (opt-out de push à main) —${NC}"
if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run]${NC} .husky/allow-main-push (libera push direto à main neste clone)"
else
    cat > .husky/allow-main-push <<EOF
# A existência deste arquivo libera o push direto à main NESTE clone (instância de
# cliente, 1 dev). É gitignored; a proteção por PR continua valendo na base.
EOF
    echo -e "  ${GREEN}✓${NC} .husky/allow-main-push criado"
fi

echo ""
echo -e "${GREEN}============================================${NC}"
if $DRY_RUN; then
    echo -e "${GREEN} Dry-run concluído. Nenhuma mudança aplicada.${NC}"
else
    echo -e "${GREEN} Cliente '${APP_NAME}' provisionado.${NC}"
fi
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e " Próximos passos:"
echo -e "  1. ${YELLOW}git push -u origin main${NC}"
echo -e "  2. ${YELLOW}ddev start && make setup${NC}"
echo -e "  3. Acesse ${YELLOW}https://${SLUG}.ddev.site/admin/setup${NC} (Setup Wizard: empresa, branding e admin)"
echo -e "  4. Updates da base: ${YELLOW}make update-base${NC}"
echo ""
