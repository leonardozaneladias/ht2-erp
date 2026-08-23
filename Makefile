# Atalhos do dia-a-dia — wrappers do DDEV.
# Pré-requisitos: OrbStack + DDEV (ver README.md / docs/devops/infra.md).
# Argumentos extras são repassados via o truque `%: @:` no fim do arquivo.
#   ex: make artisan migrate    make composer require vendor/pkg    make npm run dev

ARGS = $(filter-out $@,$(MAKECMDGOALS))

.PHONY: up down restart bash artisan migrate fresh seed horizon test \
        test-e2e composer npm dev lint quality logs status setup setup-client \
        new-client release-modulo update-base

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
	ddev artisan test --parallel --testsuite=Unit,Feature,Extensoes

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

# Setup inicial de uma INSTÂNCIA DE CLIENTE (uma vez após `ddev start`).
# Difere de `setup` (dev): NÃO roda `migrate --seed` (sem dados demo); apenas
# semeia roles+permissões (RolePermissionSeeder, idempotente) e mantém
# instalado=false, para que o Setup Wizard (/admin/setup) crie empresa/branding/admin.
setup-client:
	ddev artisan key:generate
	ddev artisan migrate --force
	ddev artisan db:seed --class=RolePermissionSeeder
	ddev artisan horizon:install
	ddev artisan vendor:publish --tag=pulse-dashboard
	ddev npm run build

# --- Instâncias por cliente (rodam no HOST, fora do container DDEV) ---
# Ver ADR-0016 e docs/distribuicao-manutencao.md.

# Provisiona um cliente após o clone + re-origin da base (aditivo; aceita --dry-run).
new-client:
	./bin/new-client.sh $(ARGS)

# Corta release de um módulo (subtree split + push + tag). Ex.:
#   make release-modulo slug=rh versao=v0.1.0
release-modulo:
	./bin/release-module.sh $(slug) $(versao)

# (NO CLIENTE) traz updates da base: git merge upstream/main + ações pós-merge.
update-base:
	./bin/update-from-upstream.sh $(ARGS)

%:
	@:
