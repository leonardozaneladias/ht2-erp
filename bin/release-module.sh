#!/usr/bin/env bash
# release-module.sh — corta um release de um pacote a partir do MONOREPO da base.
#
# Extrai packages/<dir> para o repo próprio via `git subtree split` (preservando o
# histórico daquele prefixo), empurra como `main` e cria a tag semver. A base
# continua sendo a fonte de verdade; o repo do pacote é alimentado pelos releases.
#
# O nome do repo é DERIVADO do composer.json do pacote — `ht2ml/core` vira
# `<org>/ht2ml-core`. Antes havia uma convenção hardcoded (`erp-module-<slug>`,
# `packages/modulo-<slug>`) que ficou para trás quando os pacotes passaram a se
# chamar ht2ml/*, e o script simplesmente não achava mais nada.
#
# Uso:
#   ./bin/release-module.sh <dir-do-pacote> <versao> [--dry-run]
#     ex: ./bin/release-module.sh core v0.1.0
#         ./bin/release-module.sh extensao-rh v0.2.0
set -euo pipefail

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}"

# Todo caminho passado a php/composer é ABSOLUTO de propósito: em máquinas onde
# o `php` do PATH é um shim que executa dentro de container, ele não herda o cwd
# do shell, e caminho relativo simplesmente não abre — silenciosamente, com o
# json_decode devolvendo null.

DRY_RUN=false
POSITIONAL=()
for arg in "$@"; do
    case "$arg" in
    --dry-run) DRY_RUN=true ;;
    *) POSITIONAL+=("$arg") ;;
    esac
done

if [[ ${#POSITIONAL[@]} -lt 2 ]]; then
    echo -e "${RED}Uso: ./bin/release-module.sh <dir-do-pacote> <versao> [--dry-run]${NC}"
    echo -e "     ex: ./bin/release-module.sh core v0.1.0"
    echo ""
    echo -e "Pacotes disponíveis:"
    for d in packages/*/; do
        [[ -f "${d}composer.json" ]] && printf '  %-24s %s\n' "$(basename "$d")" \
            "$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["name"];' "${ROOT_DIR}/${d}composer.json")"
    done
    exit 1
fi

DIR="${POSITIONAL[0]}"
VERSAO="${POSITIONAL[1]}"
PREFIX="packages/${DIR}"

$DRY_RUN && echo -e "${YELLOW}=== Modo dry-run: nada será empurrado ===${NC}"

# --- Validações ---
if [[ ! "${VERSAO}" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}Versão inválida: '${VERSAO}'. Use semver com 'v' (ex: v0.1.0).${NC}"
    exit 1
fi
if [[ ! -f "${PREFIX}/composer.json" ]]; then
    echo -e "${RED}'${PREFIX}/composer.json' não existe.${NC}"
    exit 1
fi
if [[ -z "$(git ls-files "${PREFIX}" | head -1)" ]]; then
    echo -e "${RED}'${PREFIX}' não está versionado — o subtree split opera sobre o histórico commitado.${NC}"
    exit 1
fi
if [[ -n "$(git status --porcelain "${PREFIX}")" ]]; then
    echo -e "${RED}Há mudanças não commitadas em ${PREFIX}.${NC}"
    echo -e "${RED}O split usaria só o que está commitado, e o release sairia diferente do que você vê.${NC}"
    exit 1
fi

PACOTE="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["name"];' "${ROOT_DIR}/${PREFIX}/composer.json")"
ORG="$(php -r 'function env($k=null,$d=null){return $d;} $c=require $argv[1]; echo $c["org"] ?? "";' \
    "${ROOT_DIR}/packages/core/config/extensoes.php")"
if [[ -z "${ORG}" || -z "${PACOTE}" ]]; then
    echo -e "${RED}Não consegui derivar org ou nome do pacote.${NC}"
    exit 1
fi

REPO="${PACOTE/\//-}"                       # ht2ml/core → ht2ml-core
REPO_URL="git@github.com:${ORG}/${REPO}.git"

echo ""
echo -e "${GREEN}— Release de '${PACOTE}' ${VERSAO} —${NC}"
echo -e "  prefix : ${PREFIX}"
echo -e "  repo   : ${ORG}/${REPO}"
echo ""

# --- A versão já existe? (o script antigo falhava feio ao repetir) ---
if git ls-remote --exit-code --tags "${REPO_URL}" "refs/tags/${VERSAO}" >/dev/null 2>&1; then
    echo -e "${RED}A tag ${VERSAO} já existe em ${ORG}/${REPO}.${NC}"
    echo -e "${RED}Releases são imutáveis: escolha a próxima versão.${NC}"
    exit 1
fi

# --- Verificação antes de publicar ---
# O `composer` pode não existir no host — em máquinas onde a toolchain vive num
# container, ele simplesmente não está no PATH. Então a checagem base é feita em
# PHP puro (que existe, é um projeto PHP), e o composer validate roda por cima só
# se estiver disponível.
echo -e "  ${YELLOW}…${NC} verificando o composer.json do pacote"
if ! php -r '
    $d = json_decode(file_get_contents($argv[1]), true);
    if (! is_array($d)) { fwrite(STDERR, "JSON inválido\n"); exit(1); }
    foreach (["name", "autoload", "require"] as $k) {
        if (! isset($d[$k])) { fwrite(STDERR, "faltando: {$k}\n"); exit(1); }
    }
    if (! isset($d["autoload"]["psr-4"]) || $d["autoload"]["psr-4"] === []) {
        fwrite(STDERR, "autoload.psr-4 vazio\n"); exit(1);
    }
' "${ROOT_DIR}/${PREFIX}/composer.json"; then
    echo -e "${RED}composer.json de ${PREFIX} está incompleto. Corrija antes de publicar.${NC}"
    exit 1
fi
if command -v composer >/dev/null 2>&1; then
    composer validate --no-check-publish --quiet --working-dir="${ROOT_DIR}/${PREFIX}" \
        || { echo -e "${RED}composer validate falhou em ${PREFIX}.${NC}"; exit 1; }
fi
echo -e "  ${GREEN}✓${NC} composer.json válido"

# --- Split (idempotente: re-computado a cada release) ---
# Feito ANTES das notas, e também no dry-run: a faixa de commits só pode ser
# calculada no histórico do split. É local e não empurra nada.
echo -e "  ${YELLOW}…${NC} git subtree split --prefix=${PREFIX}"
# -q: sem ele o subtree imprime uma linha de progresso por commit — quatrocentas
# e quarenta no core — e afoga a saída do release, inclusive as notas.
SPLIT_SHA="$(git subtree split -q --prefix="${PREFIX}")"
[[ -z "${SPLIT_SHA}" ]] && { echo -e "${RED}subtree split não retornou commit. Abortado.${NC}"; exit 1; }
echo -e "  ${GREEN}✓${NC} split → ${SPLIT_SHA}"

# --- Notas: commits que tocaram o prefixo desde a última tag publicada ---
#
# A faixa NÃO pode ser calculada com a tag direto: as tags vivem no repo do
# PACOTE e apontam para commits do histórico do split, que não existem no
# monorepo. `git log v0.1.3..HEAD` falhava com "unknown revision", o 2>/dev/null
# engolia, e todo release depois do primeiro saía com a nota "Primeiro release."
# — descoberto no dry-run do v0.2.0 do core, com quatro versões já publicadas.
#
# Buscar a tag antiga e compará-la com o split novo dá a faixa certa, com as
# mensagens originais: o subtree split preserva o commit de cada mudança no
# prefixo.
ULTIMA="$(git ls-remote --tags "${REPO_URL}" 2>/dev/null | grep -oE 'v[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1 || true)"
NOTAS=""

if [[ -n "${ULTIMA}" ]]; then
    if git fetch --quiet --no-tags "${REPO_URL}" "refs/tags/${ULTIMA}" 2>/dev/null; then
        NOTAS="$(git log --no-merges --format='- %s' "FETCH_HEAD..${SPLIT_SHA}" 2>/dev/null | head -40 || true)"
    else
        echo -e "  ${YELLOW}!${NC} não consegui buscar ${ULTIMA} de ${ORG}/${REPO}; as notas sairão do histórico inteiro"
    fi
fi

# Sem tag anterior (primeiro release) ou sem faixa: o histórico do prefixo.
[[ -z "${NOTAS}" ]] && NOTAS="$(git log --no-merges --format='- %s' "${SPLIT_SHA}" 2>/dev/null | head -40 || true)"
[[ -z "${NOTAS}" ]] && NOTAS="- Primeiro release."

if $DRY_RUN; then
    echo ""
    echo -e "  ${YELLOW}[dry-run]${NC} git push ${REPO_URL} ${SPLIT_SHA}:refs/heads/main"
    echo -e "  ${YELLOW}[dry-run]${NC} git push ${REPO_URL} ${SPLIT_SHA}:refs/tags/${VERSAO}"
    echo ""
    echo -e "  ${YELLOW}Notas que iriam no release (desde ${ULTIMA:-o início}):${NC}"
    echo "${NOTAS}" | sed 's/^/    /'
    echo ""
    echo -e "${YELLOW} Dry-run concluído.${NC}"
    exit 0
fi

# --no-verify: estes pushes vão para o REPO DO PACOTE, não a base. O pre-push da
# base bloqueia refs/heads/main em qualquer remote; aqui é intencional.
echo -e "  ${YELLOW}…${NC} push → main"
git push --no-verify "${REPO_URL}" "${SPLIT_SHA}:refs/heads/main"
echo -e "  ${YELLOW}…${NC} push → tag ${VERSAO}"
git push --no-verify "${REPO_URL}" "${SPLIT_SHA}:refs/tags/${VERSAO}"
echo -e "  ${GREEN}✓${NC} ${VERSAO} publicada"

if command -v gh >/dev/null 2>&1; then
    printf '%s\n' "${NOTAS}" | gh release create "${VERSAO}" --repo "${ORG}/${REPO}" \
        --title "${PACOTE} ${VERSAO}" --notes-file - 2>/dev/null \
        && echo -e "  ${GREEN}✓${NC} release criada no GitHub" \
        || echo -e "  ${YELLOW}!${NC} tag empurrada, mas a release do GitHub não foi criada"
fi

echo ""
echo -e "${GREEN} '${PACOTE}' ${VERSAO} publicado em ${ORG}/${REPO}.${NC}"
echo ""
echo -e " Consumo por Composer:"
echo -e "   ${YELLOW}composer config repositories.${REPO} vcs ${REPO_URL}${NC}"
echo -e "   ${YELLOW}composer require \"${PACOTE}:^${VERSAO#v}\"${NC}"
