# Atalhos do dia-a-dia — wrappers do DDEV.
# Pré-requisitos: OrbStack + DDEV (ver README.md / docs/devops/infra.md).
# Argumentos extras são repassados via o truque `%: @:` no fim do arquivo.
#   ex: make artisan migrate    make composer require vendor/pkg    make npm run dev

ARGS = $(filter-out $@,$(MAKECMDGOALS))

.PHONY: up down restart bash artisan migrate fresh seed horizon test \
        test-e2e composer npm dev lint quality logs status setup

up:
	ddev start

down:
	ddev stop

restart:
	ddev restart

bash:
	ddev ssh

artisan:
	ddev artisan $(ARGS)

migrate:
	ddev artisan migrate

fresh:
	ddev artisan migrate:fresh --seed && ddev artisan db:seed --class=DevelopmentSeeder

seed:
	ddev artisan db:seed

horizon:
	ddev exec supervisorctl restart webextradaemons:horizon

test:
	ddev artisan test --exclude-group=browser

# E2E (browser real via Playwright). Roda no HOST, não no container:
# o Chromium do Playwright vive no macOS e o vendor/ é compartilhado.
# O build do Vite é obrigatório (layout usa @vite e precisa do manifest).
test-e2e:
	npm run build && ./vendor/bin/pest --group=browser

composer:
	ddev composer $(ARGS)

npm:
	ddev npm $(ARGS)

dev:
	ddev npm run dev

lint:
	ddev exec ./vendor/bin/pint && ddev npm run format

quality:
	ddev npm run quality

logs:
	ddev logs -f

status:
	ddev describe

# Setup inicial (uma vez após `ddev start`): chave, schema+seed, assets de
# Horizon/Pulse e build de produção.
setup:
	ddev artisan key:generate
	ddev artisan migrate --seed
	ddev artisan horizon:install
	ddev artisan vendor:publish --tag=pulse-dashboard
	ddev npm run build

%:
	@:
