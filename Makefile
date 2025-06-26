### VARIABLES ###
DOCKER_COMPOSE = docker-compose --env-file .env.local
EXEC_PHP = $(DOCKER_COMPOSE) exec php
EXEC_NODE = $(DOCKER_COMPOSE) exec node
EXEC_DB = $(DOCKER_COMPOSE) exec db
CONSOLE = $(EXEC_PHP) php bin/console
XDEBUG = $(DOCKER_COMPOSE) exec -u root php php docker/php/xdebug.php
### DOCKER ###
up:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down -v

restart:
	$(DOCKER_COMPOSE) down && $(DOCKER_COMPOSE) up -d --build

build:
	$(DOCKER_COMPOSE) build

### DATABASE ###
db-drop:
	$(CONSOLE) doctrine:database:drop --force --env=dev
db-test-drop:
	$(CONSOLE) doctrine:database:drop --force --env=test
drop-schema:
	$(CONSOLE) doctrine:schema:drop --force --env=dev
drop-test-schema:
	$(CONSOLE) doctrine:schema:drop --force --env=test

db-test-create:
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists

db-create:
	$(CONSOLE) doctrine:database:create --if-not-exists --env=dev

test-migrate:
	$(CONSOLE) doctrine:migrations:migrate --env=test

migrate:
	$(CONSOLE) doctrine:migrations:migrate --env=dev

migration-diff:
	$(CONSOLE) doctrine:migrations:diff --env=dev
migration-diff-test:
	$(CONSOLE) doctrine:migrations:diff --env=test
fixtures:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

### TESTING ###
test-house-cont:
	$(EXEC_PHP) vendor/bin/phpunit tests/Controller/HouseControllerTest.php

test-booking-cont:
	$(EXEC_PHP) vendor/bin/phpunit tests/Controller/BookingControllerTest.php

### XDEBUG ###
xdebug-status:
	$(XDEBUG) status

xdebug-enable:
	$(XDEBUG) enable
	$(XDEBUG) status

xdebug-disable:
	$(XDEBUG) disable
	$(XDEBUG) status

### CODE ANALYSIS ###
phpcs:
	$(EXEC_PHP) ./vendor/bin/phpcs --standard=phpcs.xml src tests

phpcbf:
	$(EXEC_PHP) ./vendor/bin/phpcbf --standard=phpcs.xml src tests

php-cs-fix-diff:
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix --allow-risky=yes --dry-run --diff

php-cs-fix:
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix --allow-risky=yes

psalm:
	$(EXEC_PHP) ./vendor/bin/psalm