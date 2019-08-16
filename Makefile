DC=docker-compose
MYSQL_CONTAINER=information-system_mysql
PHP_CONTAINER=information-system_php
PHP_SERVICE=php
EXEC=$(DC) exec
CONSOLE=$()bin/console
COMMAND=$(EXEC) -u `id -u salah` $(PHP_SERVICE) sh -c

AWK := $(shell command -v awk 2> /dev/null)

.DEFAULT_GOAL := help
.PHONY: help

help:
ifndef AWK
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'
else
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
endif

##
## Project setup
##---------------------------------------------------------------------------

.PHONY: install
.PRECIOUS: $().env docker-compose.override.yml

install: ## Process all step in order to setup the projects
install: up
ifeq ($(CENTRAL-ON), 1)
	@echo "Resetting database for master server..."
	$(MAKE) central db-reset
endif

##
## Docker
##---------------------------------------------------------------------------
.PHONY: docker-files up down
.PRECIOUS: $().env docker-compose.override.yml

docker-files: $().env docker-compose.override.yml
.PHONY: docker-files

.env: $().env.dist
	@if [ -f $().env ]; \
	then\
		echo "\033[1;41m/!\ The .env.dist file has changed. Please check your .env file (this message will not be displayed again).\033[0m";\
		touch $().env;\
		exit 1;\
	else\
		echo cp $().env.dist $().env;\
		cp $().env.dist $().env;\
	fi

docker-compose.override.yml: docker-compose.yml
	@if [ -f docker-compose.override.yml ]; \
	then\
		echo "\033[1;41m/!\ The docker-compose.yml file has changed. Please check your docker-compose.override.yml file (this message will not be displayed again).\033[0m";\
		touch docker-compose.override.yml;\
		exit 1;\
	fi

build: ## Build the containers
build:
	@echo "Pull & build required images..."
	$(DC) build

up: ## Mount the containers
up: docker-files
	@echo "Starting containers..."
	$(DC) up -d --force-recreate --remove-orphans

down: ## Stops, remove the containers and their volumes
down: docker-files
	@echo "Stoping containers..."
	$(DC) down -v --remove-orphans

ps: ## Lists containers
ps:
	@echo "List containers..."
	docker ps

##
## Containers
##---------------------------------------------------------------------------

php: ## Connect to php container
php:
	@echo "Entering PHP container..."
	docker exec -it -u `id -u ${USER}` $(PHP_CONTAINER) bash

mysql: ## Connect to mysql container
mysql:
	@echo "Entering MySQL container..."
	docker exec -it -u `id -u ${USER}` $(MYSQL_CONTAINER) bash

##
## Cache
##---------------------------------------------------------------------------

cc: ## Clear cache
cc:
	@echo "Clearing cache..."
	$(COMMAND) "$(CONSOLE) c:c"
	$(COMMAND) "$(CONSOLE) c:w"

##
## Database
##---------------------------------------------------------------------------

db-create: ## Create database
db-create:
	@echo "Creating database..."
	$(COMMAND) "$(CONSOLE) d:d:c"

db-drop: ## Drop database
db-drop:
	@echo "Creating database..."
	$(COMMAND) "$(CONSOLE) d:d:d --force"

##
## Migrations
##---------------------------------------------------------------------------

migrate: ## Execute migrations
migrate:
	@echo "Executing migrations..."
	$(COMMAND) "$(CONSOLE) d:m:m -n"

##
## PHP-CS-FIXER
##---------------------------------------------------------------------------

fix: ## Execute php-cs-fixer
fix:
	@echo "Fix files..."
	php-cs-fixer --config=.php_cs.dist --verbose fix --diff --allow-risky=yes
