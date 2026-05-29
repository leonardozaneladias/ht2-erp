#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Lê variáveis do .env da aplicação, caindo no .env.example se ainda não existir
if [ -f "${ROOT_DIR}/.env" ]; then
    # shellcheck disable=SC1091
    set -a; source "${ROOT_DIR}/.env"; set +a
elif [ -f "${ROOT_DIR}/.env.example" ]; then
    # shellcheck disable=SC1091
    set -a; source "${ROOT_DIR}/.env.example"; set +a
fi

APP_NAME="${APP_NAME:-Laravel Admin}"
DB_USERNAME="${DB_USERNAME:-laravel_admin}"

echo -e "${GREEN}=== ${APP_NAME} - Setup Docker ===${NC}"

cd "${ROOT_DIR}/laradock"

if [ ! -f .env ]; then
    echo -e "${YELLOW}Criando .env do Laradock a partir do .env.example...${NC}"
    cp .env.example .env
fi

echo -e "${GREEN}[1/6] Subindo containers...${NC}"
docker compose up -d \
    workspace \
    php-fpm \
    nginx \
    postgres \
    redis \
    laravel-horizon \
    pgadmin \
    mailpit

echo -e "${GREEN}[2/6] Aguardando Postgres ficar pronto...${NC}"
sleep 5
docker compose exec postgres pg_isready -U "${DB_USERNAME}" || sleep 5

echo -e "${GREEN}[3/6] Instalando dependências PHP...${NC}"
docker compose exec workspace bash -c "cd /var/www && composer install"

echo -e "${GREEN}[4/6] Configurando aplicação Laravel...${NC}"
docker compose exec workspace bash -c "
    cd /var/www
    [ ! -f .env ] && cp .env.example .env
    php artisan key:generate --ansi
    php artisan migrate --force
    php artisan horizon:install
    php artisan vendor:publish --provider='Laravel\Pulse\PulseServiceProvider'
    php artisan optimize:clear
    php artisan view:clear
"

echo -e "${GREEN}[5/6] Instalando dependências Node.js...${NC}"
docker compose exec workspace bash -c "cd /var/www && npm install && npm run build"

echo -e "${GREEN}[6/6] Reiniciando Horizon e PHP-FPM...${NC}"
docker compose restart laravel-horizon php-fpm

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN} Setup concluído com sucesso!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e " App:       ${YELLOW}http://localhost${NC}"
echo -e " Horizon:   ${YELLOW}http://localhost/horizon${NC}"
echo -e " Pulse:     ${YELLOW}http://localhost/pulse${NC}"
echo -e " pgAdmin:   ${YELLOW}http://localhost:5050${NC}"
echo -e " Mailpit:   ${YELLOW}http://localhost:8125${NC}"
echo ""
