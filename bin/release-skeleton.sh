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

# Configs que o monorepo tem mas que NÃO servem a um produto: o phpstan.neon
# daqui aponta para packages/*, que não existe no consumidor, e o phpstan morre
# com "Path does not exist" no primeiro uso. Os stubs sobrescrevem.
for stub in "${ROOT_DIR}"/stubs/skeleton/*; do
    [[ -f "${stub}" ]] || continue
    cp "${stub}" "${TMP}/$(basename "${stub}")"
done

# CI: vem de stubs/skeleton/.github, não do .github deste monorepo — o daqui
# testa a plataforma, e o do produto precisa autenticar nos pacotes privados.
if [[ -d "stubs/skeleton/.github" ]]; then
    cp -R "stubs/skeleton/.github" "${TMP}/.github"
fi

# O .gitattributes do monorepo marca /.github como export-ignore — convenção
# correta para BIBLIOTECA (ninguém quer CI dentro de vendor/) e errada para
# TEMPLATE DE PROJETO: o composer create-project baixa o tarball do GitHub, que
# respeita export-ignore, e o produto novo nascia sem CI nenhum. O skeleton
# exporta tudo.
if [[ -f "${TMP}/.gitattributes" ]]; then
    grep -v 'export-ignore' "${TMP}/.gitattributes" > "${TMP}/.gitattributes.novo" || true
    mv "${TMP}/.gitattributes.novo" "${TMP}/.gitattributes"
fi

# Testes: só o andaime (TestCase, Pest.php, Arch). A suíte da plataforma testa o
# core e vive no monorepo — carregá-la aqui daria um skeleton que falha de saída.
mkdir -p "${TMP}/tests/Feature" "${TMP}/tests/Unit"
# .gitkeep porque o git não versiona diretório vazio, e o phpunit.xml declara
# tests/Unit como suíte: sem o arquivo, o publish perde a pasta e o
# `php artisan test` do produto novo morre com "Test directory not found".
touch "${TMP}/tests/Unit/.gitkeep"
for f in tests/TestCase.php tests/Pest.php; do
    [[ -f "${f}" ]] && cp "${f}" "${TMP}/${f}"
done
cat > "${TMP}/tests/Feature/SmokeTest.php" <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * O único teste que o skeleton traz: a plataforma subiu?
 *
 * Se ht2ml/core está instalado e registrado, a tela de login do admin responde.
 * É o mínimo que prova que o create-project entregou algo funcional.
 *
 * RefreshDatabase é obrigatório aqui: as configurações do núcleo são lidas da
 * tabela `settings` no boot, e sem schema a página estoura com
 * "no such table: settings" em vez de renderizar.
 */
uses(RefreshDatabase::class);

it('serve a tela de login do admin', function () {
    $this->get('/admin/login')->assertOk();
});
PHP

# A restrição do core é a ÚLTIMA VERSÃO PUBLICADA DELE, não a versão do skeleton
# — os dois versionam independente. Usar a do skeleton fazia v0.2.1 exigir
# `ht2ml/core: ^0.2.1`, que não existe, e o composer install do produto novo
# morria com "does not match the constraint". Funcionou nos primeiros releases
# só porque os números coincidiam.
CORE_URL="git@github.com:${ORG}/ht2ml-core.git"
CORE_VER="$(git ls-remote --tags "${CORE_URL}" 2>/dev/null \
    | grep -oE 'v[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -1 || true)"
if [[ -z "${CORE_VER}" ]]; then
    echo -e "${RED}Não achei nenhuma tag em ${ORG}/ht2ml-core — publique o core antes do skeleton.${NC}"
    exit 1
fi
echo -e "  ${GREEN}✓${NC} core publicado mais recente: ${CORE_VER}"

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
        // no-api: o Composer usa GIT em vez da API do GitHub para estes
        // repositórios. Sem isso ele baixa um zipball de api.github.com, que
        // exige o token ter permissão na API — caminho que falha com
        // "Could not authenticate against github.com" mesmo quando o mesmo
        // token já autentica as operações git. Com no-api, uma credencial só
        // (a reescrita de URL do CI) cobre tudo.
        $d["repositories"][] = [
            "type" => "vcs",
            "url" => "git@github.com:{$org}/ht2ml-{$slug}.git",
            "no-api" => true,
        ];
    }
    // Só o core entra por padrão. Extensão é escolha do produto — e o demo
    // existe justamente para provar que dá para NÃO instalar.
    foreach (array_keys($d["require"]) as $k) {
        if (str_starts_with($k, "ht2ml/")) { unset($d["require"][$k]); }
    }
    $d["require"]["ht2ml/core"] = $argv[3];
    // Os ht2ml/* vêm por GIT; todo o resto por zip.
    //
    // O zip vem de api.github.com, e para repositório privado isso exige o
    // token ter permissão na API — canal diferente do que autentica o git. Um
    // CI que reescreve SSH para HTTPS resolve os refs e ainda assim morre no
    // download, com "Could not authenticate against github.com". Declarar a
    // preferência por pacote faz uma credencial só cobrir tudo, sem depender de
    // fallback (que o Composer desliga em modo não interativo).
    $d["config"] = ($d["config"] ?? []) + [];
    $d["config"]["preferred-install"] = ["ht2ml/*" => "source", "*" => "dist"];
    ksort($d["require"]);
    file_put_contents($p, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
' "${TMP}/composer.json" "${ORG}" "^${CORE_VER#v}"

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

# Os assets NÃO vêm prontos: o Composer não carrega dependência npm, e o
# Tailwind precisa gerar o CSS varrendo os blades — inclusive os que vivem
# dentro de vendor/ht2ml/core. Sem este passo, toda tela devolve 500 com
# "Vite manifest not found".
npm install && npm run build
```

Depois disso, `/admin/login` responde. Os seeders criam `admin@example.com` /
`password` (super-admin) e `gestor@example.com` / `password`.

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

# O produto não pode nascer reprovando o próprio job de qualidade. Os JSON que
# este script reescreve saem com formatação diferente da que o Prettier espera.
if command -v npx >/dev/null 2>&1; then
    (cd "${TMP}" && npx --yes prettier --write \
        "composer.json" "package.json" "README.md" ".prettierrc" >/dev/null 2>&1) || true
fi

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
