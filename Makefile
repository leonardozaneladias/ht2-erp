LARADOCK_DIR=laradock
SERVICES=workspace php-fpm nginx postgres redis laravel-horizon pgadmin mailpit

.PHONY: up down restart bash artisan migrate fresh seed horizon test logs status setup

up:
	cd $(LARADOCK_DIR) && docker compose up -d $(SERVICES)

down:
	cd $(LARADOCK_DIR) && docker compose down

restart:
	cd $(LARADOCK_DIR) && docker compose restart $(SERVICES)

build:
	cd $(LARADOCK_DIR) && docker compose build $(SERVICES)

bash:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash

artisan:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && php artisan $(filter-out $@,$(MAKECMDGOALS))"

migrate:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && php artisan migrate"

fresh:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && php artisan migrate:fresh --seed && php artisan db:seed --class=DevelopmentSeeder"

seed:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && php artisan db:seed"

horizon:
	cd $(LARADOCK_DIR) && docker compose restart laravel-horizon

test:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && php artisan test"

composer:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && composer $(filter-out $@,$(MAKECMDGOALS))"

npm:
	cd $(LARADOCK_DIR) && docker compose exec workspace bash -c "cd /var/www && npm $(filter-out $@,$(MAKECMDGOALS))"

logs:
	cd $(LARADOCK_DIR) && docker compose logs -f --tail=100

status:
	cd $(LARADOCK_DIR) && docker compose ps

setup:
	./docker-setup.sh

%:
	@:
