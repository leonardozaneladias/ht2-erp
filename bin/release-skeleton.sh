#!/usr/bin/env bash
# release-skeleton.sh — publica ht2ml/skeleton a partir DESTE monorepo.
#
# O skeleton é o que `composer create-project ht2ml/skeleton` entrega: um app
# Laravel vazio de plataforma, que INSTALA ht2ml/core por Composer (ADR-0017).
#
# Ele é GERADO daqui, não mantido à parte, porque duas cópias do mesmo app
# divergem — e a que ninguém roda diverge primeiro. Este repositório é a
# referência viva: se ele boota, o skeleton boota.
#
# Nada de regra "copie tudo menos X": depois que o demo e os documentos viraram
# pacotes, o app/ do monorepo TEM exatamente o que o skeleton precisa. A lista
# abaixo é de inclusão, e é curta de propósito.
#
# Uso:
#   ./bin/release-skeleton.sh <versao> [--dry-run]
#     ex: ./bin/release-skeleton.sh v0.1.0
set -euo pipefail

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "${ROOT_DIR}"

DRY_RUN=false; POSITIONAL=()
for arg in "$@"; do
    case "$arg" in --dry-run) DRY_RUN=true ;; *) POSITIONAL+=("$arg") ;; esac
done
[[ ${#POSITIONAL[@]} -lt 1 ]] && { echo -e "${RED}Uso: ./bin/release-skeleton.sh <versao> [--dry-run]${NC}"; exit 1; }
VERSAO="${POSITIONAL[0]}"
[[ ! "${VERSAO}" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] && { echo -e "${RED}Versão inválida: '${VERSAO}'.${NC}"; exit 1; }

ORG="$(php -r 'function env($k=null,$d=null){return $d;} $c=require $argv[1]; echo $c["org"] ?? "";' \
    "${ROOT_DIR}/packages/core/config/extensoes.php")"
REPO="ht2ml-skeleton"
REPO_URL="git@github.com:${ORG}/${REPO}.git"

# O que o app hospedeiro tem. Tudo o mais é plataforma (vive nos pacotes) ou
# ferramenta do monorepo (docs/, bin/, packages/, Makefile).
INCLUIR=(
    app bootstrap config database public resources routes storage lang
    artisan composer.json package.json vite.config.js
    phpunit.xml pint.json phpstan.neon eslint.config.js
    .editorconfig .gitattributes .gitignore .env.example .prettierrc .prettierignore
)

echo ""
echo -e "${GREEN}— Skeleton ${VERSAO} → ${ORG}/${REPO} —${NC}"

# Dentro do repositório, não em /tmp: o `php` do PATH pode ser um shim que roda
# em container, e /var/folders (o mktemp do macOS) não está montado lá — o
# file_get_contents falha sem que nada no shell denuncie.
TMP="${ROOT_DIR}/.skeleton-build"
rm -rf "${TMP}"
mkdir -p "${TMP}"
trap 'rm -rf "${TMP}"' EXIT

for item in "${INCLUIR[@]}"; do
    [[ -e "${item}" ]] || { echo -e "  ${YELLOW}!${NC} ausente, pulando: ${item}"; continue; }
    mkdir -p "${TMP}/$(dirname "${item}")"
    cp -R "${item}" "${TMP}/${item}"
done

# Testes: só o andaime (TestCase, Pest.php, Arch). A suíte da plataforma testa o
# core e vive no monorepo — carregá-la aqui daria um skeleton que falha de saída.
mkdir -p "${TMP}/tests/Feature" "${TMP}/tests/Unit"
for f in tests/TestCase.php tests/Pest.php; do
    [[ -f "${f}" ]] && cp "${f}" "${TMP}/${f}"
done
cat > "${TMP}/tests/Feature/SmokeTest.php" <<'PHP'
<?php

declare(strict_types=1);

/**
 * O único teste que o skeleton traz: a plataforma subiu?
 *
 * Se ht2ml/core está instalado e registrado, a tela de login do admin responde.
 * É o mínimo que prova que o create-project entregou algo funcional.
 */
it('serve a tela de login do admin', function () {
    $this->get('/admin/login')->assertOk();
});
PHP

# composer.json: sai o path repository do monorepo, entram os VCS dos pacotes.
php -r '
    $p = $argv[1];
    $d = json_decode(file_get_contents($p), true);
    $d["name"] = "ht2ml/skeleton";
    $d["description"] = "Esqueleto de produto da plataforma HT2ML — instala ht2ml/core por Composer.";
    $d["type"] = "project";
    $org = $argv[2];
    $d["repositories"] = [];
    foreach (["core", "extensao-rh", "extensao-fiscal-br", "extensao-exemplo-demo", "extensao-documentos"] as $slug) {
        $d["repositories"][] = ["type" => "vcs", "url" => "git@github.com:{$org}/ht2ml-{$slug}.git"];
    }
    // Só o core entra por padrão. Extensão é escolha do produto — e o demo
    // existe justamente para provar que dá para NÃO instalar.
    foreach (array_keys($d["require"]) as $k) {
        if (str_starts_with($k, "ht2ml/")) { unset($d["require"][$k]); }
    }
    $d["require"]["ht2ml/core"] = $argv[3];
    ksort($d["require"]);
    file_put_contents($p, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
' "${TMP}/composer.json" "${ORG}" "^${VERSAO#v}"

# Sobras do monorepo que não fazem sentido num produto novo.
rm -f "${TMP}/composer.lock" "${TMP}/package-lock.json"

# Esvazia o storage SEM apagar os .gitignore de cada pasta: são eles que fazem o
# git versionar o diretório vazio. Apagá-los publica um skeleton sem
# storage/framework/views, e o primeiro artisan morre com
# "Please provide a valid cache path" — foi o que aconteceu na primeira tentativa.
find "${TMP}/storage" -type f ! -name '.gitignore' -delete 2>/dev/null || true
for d in framework/views framework/cache/data framework/sessions framework/testing logs app/public; do
    mkdir -p "${TMP}/storage/${d}"
    [[ -f "${TMP}/storage/${d}/.gitignore" ]] || printf '*\n!.gitignore\n' > "${TMP}/storage/${d}/.gitignore"
done
# bootstrap/cache idem.
mkdir -p "${TMP}/bootstrap/cache"
find "${TMP}/bootstrap/cache" -type f ! -name '.gitignore' -delete 2>/dev/null || true
[[ -f "${TMP}/bootstrap/cache/.gitignore" ]] || printf '*\n!.gitignore\n' > "${TMP}/bootstrap/cache/.gitignore"

cat > "${TMP}/README.md" <<'MD'
# Esqueleto de produto — plataforma HT2ML

Gerado a partir do monorepo da base (`bin/release-skeleton.sh`). Não edite aqui:
mude no monorepo e corte um release novo.

```bash
composer create-project ht2ml/skeleton meu-produto
cd meu-produto
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
```

O que vem: um app Laravel que **instala** `ht2ml/core` por Composer — auth com
2FA, multiempresa, perfis e permissões, auditoria, configurações, menu, design
system e catálogos de referência. Nada disso vive neste repositório.

## Extensões

Só o core entra por padrão. As demais são escolha do produto:

```bash
composer require ht2ml/extensao-rh          # RH / departamento pessoal
composer require ht2ml/extensao-fiscal-br   # CNAE, CFOP, NCM
composer require ht2ml/extensao-documentos  # numeração sequencial
composer require ht2ml/extensao-exemplo-demo # vitrine do design system
```

Os repositórios já estão declarados no `composer.json`.
MD

TOTAL="$(find "${TMP}" -type f | wc -l | tr -d ' ')"
echo -e "  ${GREEN}✓${NC} árvore montada: ${TOTAL} arquivos"

if $DRY_RUN; then
    echo ""
    echo -e "  ${YELLOW}[dry-run]${NC} conteúdo de primeiro nível:"
    (cd "${TMP}" && ls -A) | sed 's/^/      /'
    echo ""
    echo -e "  ${YELLOW}[dry-run]${NC} require do composer.json:"
    php -r '$d=json_decode(file_get_contents($argv[1]),true); foreach($d["require"] as $k=>$v) echo "      $k: $v\n";' "${TMP}/composer.json"
    echo -e "${YELLOW} Dry-run concluído — nada empurrado.${NC}"
    exit 0
fi

if git ls-remote --exit-code --tags "${REPO_URL}" "refs/tags/${VERSAO}" >/dev/null 2>&1; then
    echo -e "${RED}A tag ${VERSAO} já existe em ${ORG}/${REPO}. Releases são imutáveis.${NC}"
    exit 1
fi

cd "${TMP}"
git init -q -b main
git add -A
git -c user.email=skeleton@ht2ml.local -c user.name="HT2ML Skeleton" \
    commit -q -m "chore: skeleton ${VERSAO} gerado do monorepo da base"
# Anotada: a config local exige mensagem em tag (forceSignAnnotated/annotate).
    git -c user.email=skeleton@ht2ml.local -c user.name="HT2ML Skeleton" \
        tag -a "${VERSAO}" -m "skeleton ${VERSAO}"
git push --no-verify -q --force "${REPO_URL}" "main:refs/heads/main"
git push --no-verify -q "${REPO_URL}" "refs/tags/${VERSAO}"
cd "${ROOT_DIR}"

echo -e "  ${GREEN}✓${NC} ${VERSAO} publicada em ${ORG}/${REPO}"
echo ""
echo -e " Uso:"
echo -e "   ${YELLOW}composer create-project ht2ml/skeleton meu-produto${NC}"
