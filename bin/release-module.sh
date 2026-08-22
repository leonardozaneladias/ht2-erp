#!/usr/bin/env bash
# release-module.sh — corta um release de um módulo a partir do MONOREPO da base.
#
# Extrai packages/modulo-<slug> para o repo próprio do módulo via `git subtree split`
# (preservando o histórico daquele prefixo), empurra como `main` e cria a tag semver.
# A base continua sendo a fonte de verdade; o repo do módulo é alimentado pelos releases.
# Ver ADR-0016 e docs/distribuicao-manutencao.md (seção 3).
#
# Pré-requisitos:
#   - rodar na raiz da base (onde packages/modulo-<slug> é versionado);
#   - o repo do módulo já existir: gh repo create <org>/erp-module-<slug> --private
#
# Uso:
#   ./bin/release-module.sh <slug> <versao>            # ex: ./bin/release-module.sh rh v0.1.0
#   ./bin/release-module.sh <slug> <versao> --dry-run  # mostra o que faria sem empurrar
#   (via make:  make release-modulo slug=rh versao=v0.1.0)

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}"

# Convenção do nome do repo do módulo (ver comentário em config/extensoes.php).
REPO_PREFIX="erp-module-"

# --- Parse de argumentos (2 posicionais + --dry-run em qualquer posição) ---
DRY_RUN=false
POSITIONAL=()
for arg in "$@"; do
    case "$arg" in
    --dry-run) DRY_RUN=true ;;
    *) POSITIONAL+=("$arg") ;;
    esac
done

if [[ ${#POSITIONAL[@]} -lt 2 ]]; then
    echo -e "${RED}Uso: ./bin/release-module.sh <slug> <versao> [--dry-run]${NC}"
    echo -e "     ex: ./bin/release-module.sh rh v0.1.0"
    exit 1
fi

SLUG="${POSITIONAL[0]}"
VERSAO="${POSITIONAL[1]}"

$DRY_RUN && echo -e "${YELLOW}=== Modo dry-run: nada será empurrado ===${NC}"

# --- Validações ---
if [[ ! "${VERSAO}" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}Versão inválida: '${VERSAO}'. Use semver com 'v' (ex: v0.1.0).${NC}"
    exit 1
fi

PREFIX="packages/modulo-${SLUG}"
if [[ ! -d "${PREFIX}" ]]; then
    echo -e "${RED}Pasta '${PREFIX}' não existe.${NC}"
    exit 1
fi
if [[ -z "$(git ls-files "${PREFIX}" | head -1)" ]]; then
    echo -e "${RED}'${PREFIX}' não está versionado na base (monorepo).${NC}"
    echo -e "${RED}O subtree split opera sobre o histórico commitado desse prefixo.${NC}"
    exit 1
fi

# --- Lê a org de config/extensoes.php (stub de env() evita bootar o Laravel) ---
read_cfg() {
    php -r 'function env($k=null,$d=null){return $d;} $c=require $argv[1]; echo $c[$argv[2]] ?? "";' \
        "config/extensoes.php" "$1"
}
ORG="$(read_cfg org)"
if [[ -z "${ORG}" ]]; then
    echo -e "${RED}Não consegui ler 'org' de config/extensoes.php.${NC}"
    exit 1
fi

REPO="${REPO_PREFIX}${SLUG}"
REPO_URL="git@github.com:${ORG}/${REPO}.git"

echo ""
echo -e "${GREEN}— Release do módulo '${SLUG}' ${VERSAO} —${NC}"
echo -e "  prefix : ${PREFIX}"
echo -e "  repo   : ${REPO_URL}"
echo ""

# Aviso se houver mudanças não commitadas no prefixo (não entram no split).
if [[ -n "$(git status --porcelain "${PREFIX}")" ]]; then
    echo -e "  ${YELLOW}!${NC} Há mudanças não commitadas em ${PREFIX} — o split usa só o que está commitado."
fi

if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run]${NC} git subtree split --prefix=${PREFIX}  → <sha>"
    echo -e "  ${YELLOW}[dry-run]${NC} git push ${REPO_URL} <sha>:refs/heads/main"
    echo -e "  ${YELLOW}[dry-run]${NC} git push ${REPO_URL} <sha>:refs/tags/${VERSAO}"
    echo ""
    echo -e "${YELLOW} Dry-run concluído.${NC}"
    exit 0
fi

# --- Split (idempotente: re-computado a cada release) ---
echo -e "  ${YELLOW}…${NC} git subtree split --prefix=${PREFIX}"
SPLIT_SHA="$(git subtree split --prefix="${PREFIX}")"
if [[ -z "${SPLIT_SHA}" ]]; then
    echo -e "${RED}subtree split não retornou commit. Abortado.${NC}"
    exit 1
fi
echo -e "  ${GREEN}✓${NC} split → ${SPLIT_SHA}"

# --- Push do conteúdo como main e da tag (sem poluir refs locais da base) ---
# --no-verify: estes pushes vão para o REPO DO MÓDULO (não a base). O pre-push da
# base bloqueia refs/heads/main em qualquer remote; aqui é intencional e correto.
echo -e "  ${YELLOW}…${NC} push → main"
git push --no-verify "${REPO_URL}" "${SPLIT_SHA}:refs/heads/main"
echo -e "  ${GREEN}✓${NC} main empurrada"

echo -e "  ${YELLOW}…${NC} push → tag ${VERSAO}"
git push --no-verify "${REPO_URL}" "${SPLIT_SHA}:refs/tags/${VERSAO}"
echo -e "  ${GREEN}✓${NC} tag ${VERSAO} criada"

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN} Módulo '${SLUG}' ${VERSAO} publicado em ${ORG}/${REPO}.${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e " Consumo por Composer (quando ativar — ver ADR-0016):"
echo -e "   ${YELLOW}repositories${NC}: { \"type\": \"vcs\", \"url\": \"${REPO_URL}\" }"
echo -e "   ${YELLOW}composer require \"$(read_cfg vendor)/$(read_cfg prefixo_pacote)${SLUG}:^${VERSAO#v}\"${NC}"
echo ""
