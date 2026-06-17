#!/usr/bin/env bash
# update-from-upstream.sh — traz updates da BASE para esta instância de cliente.
#
# Roda NO CLIENTE (vem versionado da base, via clone). Faz:
#   git fetch upstream && git merge --no-edit upstream/main
# e, se o merge for limpo e trouxer novidade, roda as ações pós-merge:
#   php artisan migrate --force && php artisan access:sync && php artisan cache:clear
# (usa `ddev artisan` automaticamente quando em DDEV).
#
# Em conflito, para e instrui — resolva, commite e rode as ações pós-merge à mão.
# Ver ADR-0016 e docs/distribuicao-manutencao.md (seção 5).
#
# Uso:
#   ./bin/update-from-upstream.sh            # fetch + merge + pós-merge
#   ./bin/update-from-upstream.sh --dry-run  # só mostra os commits que entrariam
#   (via make:  make update-base)

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}"

DRY_RUN=false
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN=true

# --- upstream precisa existir (ver new-client.sh / re-origin) ---
if ! git remote | grep -qx upstream; then
    echo -e "${RED}Remote 'upstream' não configurado.${NC}"
    echo -e "Configure a base como upstream (no clone do cliente):"
    echo -e "  ${YELLOW}git remote add upstream git@github.com:leonardozaneladias/ht2-erp.git${NC}"
    exit 1
fi

echo -e "${GREEN}⬇  Atualizando a partir da base (upstream/main)${NC}"
echo -e "  ${YELLOW}…${NC} git fetch upstream"
git fetch upstream

INCOMING="$(git log --oneline HEAD..upstream/main 2>/dev/null || true)"
if [[ -z "${INCOMING}" ]]; then
    echo -e "  ${GREEN}✓${NC} Nada novo na base — já atualizado."
    exit 0
fi

echo ""
echo -e "${GREEN}— Commits que entrariam —${NC}"
echo "${INCOMING}"
echo ""

if $DRY_RUN; then
    echo -e "${YELLOW} Dry-run: nenhum merge feito. Rode sem --dry-run para aplicar.${NC}"
    exit 0
fi

# --- Merge ---
BEFORE="$(git rev-parse HEAD)"
if ! git merge --no-edit upstream/main; then
    echo ""
    echo -e "${RED}[conflito] O merge parou em conflito.${NC}"
    echo -e "Resolva, então:"
    echo -e "  ${YELLOW}git add -A && git commit${NC}"
    echo -e "  ${YELLOW}php artisan migrate --force && php artisan access:sync && php artisan cache:clear${NC}"
    echo -e "Para abortar:  ${YELLOW}git merge --abort${NC}"
    exit 1
fi
AFTER="$(git rev-parse HEAD)"

if [[ "${BEFORE}" == "${AFTER}" ]]; then
    echo -e "  ${GREEN}✓${NC} Merge sem mudanças efetivas."
    exit 0
fi
echo -e "  ${GREEN}✓${NC} Merge limpo."

# --- Ações pós-merge (ddev artisan se em DDEV; senão php artisan) ---
if command -v ddev >/dev/null 2>&1 && [[ -f .ddev/config.yaml ]]; then
    RUN=(ddev artisan)
    echo -e "  ${YELLOW}(usando ddev artisan)${NC}"
else
    RUN=(php artisan)
fi

echo ""
echo -e "${GREEN}— Ações pós-merge —${NC}"
echo -e "  ${YELLOW}…${NC} migrate --force"
"${RUN[@]}" migrate --force
echo -e "  ${YELLOW}…${NC} access:sync"
"${RUN[@]}" access:sync
echo -e "  ${YELLOW}…${NC} cache:clear"
"${RUN[@]}" cache:clear

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN} Base aplicada. Confira o CHANGELOG por ações extras.${NC}"
echo -e "${GREEN}============================================${NC}"
