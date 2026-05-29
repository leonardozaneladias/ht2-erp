#!/usr/bin/env bash
# init-project.sh — renomeia este boilerplate para um novo projeto.
#
# Uso:
#   ./bin/init-project.sh           # modo interativo
#   ./bin/init-project.sh --dry-run # mostra o que mudaria sem editar
#
# Substitui em:
#   composer.json    → name = "<slug>/admin"
#   package.json     → name = "<slug>-admin"
#   .env.example     → APP_NAME, DB_DATABASE, DB_USERNAME, HORIZON_PREFIX,
#                      PULSE_SERVER_NAME, MAIL_FROM_ADDRESS
#   .env (cria a partir do .env.example se não existir)
#   README.md, CLAUDE.md, AGENTS.md → primeira linha de título
#
# Opcional (com confirmação): limpa CHANGELOG, memória do Claude, planos
# do superpowers e reinicializa git history.

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
    echo -e "${YELLOW}=== Modo dry-run: nenhum arquivo será modificado ===${NC}"
fi

echo -e "${GREEN}🚀 Inicializando novo projeto a partir do boilerplate${NC}"
echo ""

if $DRY_RUN; then
    APP_NAME="Sistema ACME"
    SLUG="acme"
    MAIL_DOMAIN="acme.com.br"
    echo -e "${YELLOW}(valores fictícios em dry-run: APP_NAME='${APP_NAME}', SLUG='${SLUG}', MAIL_DOMAIN='${MAIL_DOMAIN}')${NC}"
else
    read -p "Nome do projeto (ex: 'Sistema ACME'): " APP_NAME
    read -p "Slug (minúsculas, sem espaços — ex: 'acme'): " SLUG
    read -p "Domínio de e-mail (ex: 'acme.com.br'): " MAIL_DOMAIN

    if [[ -z "${APP_NAME}" || -z "${SLUG}" || -z "${MAIL_DOMAIN}" ]]; then
        echo -e "${RED}Abortado: todos os campos são obrigatórios.${NC}"
        exit 1
    fi

    if [[ ! "${SLUG}" =~ ^[a-z][a-z0-9-]*$ ]]; then
        echo -e "${RED}Slug inválido. Use apenas letras minúsculas, números e hífen.${NC}"
        exit 1
    fi
fi

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

echo ""
echo -e "${GREEN}— Aplicando renomeações —${NC}"

# composer.json
if [[ -f composer.json ]]; then
    apply "composer.json → name '${SLUG}/admin'" \
        sed_inplace "s|\"name\": \"boilerplate/laravel-admin-inspinia\"|\"name\": \"${SLUG}/admin\"|" composer.json
fi

# package.json
if [[ -f package.json ]]; then
    apply "package.json → name '${SLUG}-admin'" \
        sed_inplace "s|\"name\": \"laravel-admin-inspinia\"|\"name\": \"${SLUG}-admin\"|" package.json
fi

# .env.example
if [[ -f .env.example ]]; then
    apply ".env.example → APP_NAME, DB_*, HORIZON_PREFIX, PULSE, MAIL" \
        bash -c "
        sed_inplace_fn() { if sed --version >/dev/null 2>&1; then sed -i \"\$@\"; else sed -i '' \"\$@\"; fi; }
        sed_inplace_fn 's|APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|' .env.example
        sed_inplace_fn 's|DB_DATABASE=.*|DB_DATABASE=${SLUG}|' .env.example
        sed_inplace_fn 's|DB_USERNAME=.*|DB_USERNAME=${SLUG}|' .env.example
        sed_inplace_fn 's|HORIZON_PREFIX=.*|HORIZON_PREFIX=${SLUG}_horizon:|' .env.example
        sed_inplace_fn 's|MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"noreply@${MAIL_DOMAIN}\"|' .env.example
        "
fi

# .env (cria se não existe)
if [[ ! -f .env && -f .env.example ]]; then
    apply ".env (cópia de .env.example)" cp .env.example .env
elif [[ -f .env ]]; then
    echo -e "  ${YELLOW}!${NC} .env já existe — revise manualmente"
fi

# Títulos em README.md / CLAUDE.md / AGENTS.md
for arquivo in README.md CLAUDE.md AGENTS.md; do
    if [[ -f "${arquivo}" ]]; then
        apply "${arquivo} → título '${APP_NAME}'" \
            sed_inplace "1s|^# .*|# ${APP_NAME}|" "${arquivo}"
    fi
done

echo ""
echo -e "${GREEN}— Limpeza opcional (confirme cada bloco) —${NC}"

if ! $DRY_RUN; then
    read -p "Resetar CHANGELOG.md como stub limpo? [y/N]: " R
    if [[ "${R}" =~ ^[yY]$ ]]; then
        cat > CHANGELOG.md <<EOF
# Changelog

Mantido no padrão [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

## [Unreleased]

- Início do projeto a partir do boilerplate Laravel Admin + Inspinia.
EOF
        echo -e "  ${GREEN}✓${NC} CHANGELOG.md resetado"
    fi

    read -p "Limpar .claude/memory-log.md (memória local do Claude do boilerplate)? [y/N]: " R
    if [[ "${R}" =~ ^[yY]$ ]] && [[ -f .claude/memory-log.md ]]; then
        : > .claude/memory-log.md
        echo -e "  ${GREEN}✓${NC} .claude/memory-log.md zerado"
    fi

    read -p "Apagar planos/specs antigos em docs/superpowers/? [y/N]: " R
    if [[ "${R}" =~ ^[yY]$ ]]; then
        rm -rf docs/superpowers/plans/* docs/superpowers/specs/* 2>/dev/null || true
        echo -e "  ${GREEN}✓${NC} docs/superpowers/plans|specs/ zerados"
    fi

    read -p "Reinicializar git history (rm -rf .git && git init && commit inicial)? [y/N]: " R
    if [[ "${R}" =~ ^[yY]$ ]]; then
        rm -rf .git
        git init -q
        git add -A
        git commit -q -m "chore: bootstrap ${APP_NAME}"
        echo -e "  ${GREEN}✓${NC} git history reiniciado"
    fi
fi

echo ""
echo -e "${GREEN}============================================${NC}"
if $DRY_RUN; then
    echo -e "${GREEN} Dry-run concluído. Nenhum arquivo alterado.${NC}"
else
    echo -e "${GREEN} Projeto '${APP_NAME}' inicializado.${NC}"
fi
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e " Próximos passos:"
echo -e "  1. Revise .env e ajuste senhas/segredos"
echo -e "  2. Rode  ${YELLOW}./docker-setup.sh${NC}  para subir o ambiente"
echo -e "  3. Acesse  ${YELLOW}http://localhost/admin/login${NC}  (admin@example.com / password)"
echo ""
