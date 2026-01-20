.PHONY: help install start stop restart logs shell test qa lint phpstan cs-fix migrate fixtures jwt-keys clean build deploy

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

## Help
help: ## Show this help
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## —— Docker ————————————————————————————————————————————————————————————————
install: ## Install the project
	@docker compose build
	@docker compose up -d
	@docker compose exec php composer install
	@$(MAKE) jwt-keys
	@$(MAKE) migrate
	@echo "${GREEN}Project installed successfully!${RESET}"

start: ## Start containers
	@docker compose up -d

stop: ## Stop containers
	@docker compose down

restart: stop start ## Restart containers

logs: ## Show logs
	@docker compose logs -f

shell: ## Open a shell in PHP container
	@docker compose exec php sh

db-shell: ## Open a PostgreSQL shell
	@docker compose exec database psql -U smartcafe -d smartcafe

## —— Composer ——————————————————————————————————————————————————————————————
composer-install: ## Install Composer dependencies
	@docker compose exec php composer install

composer-update: ## Update Composer dependencies
	@docker compose exec php composer update

## —— Symfony ———————————————————————————————————————————————————————————————
cache-clear: ## Clear Symfony cache
	@docker compose exec php php bin/console cache:clear

migrate: ## Run database migrations
	@docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

migrate-diff: ## Generate migration diff
	@docker compose exec php php bin/console doctrine:migrations:diff

fixtures: ## Load fixtures
	@docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

jwt-keys: ## Generate JWT keys
	@docker compose exec php mkdir -p config/jwt
	@docker compose exec php openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:smartcafe
	@docker compose exec php openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:smartcafe
	@echo "${GREEN}JWT keys generated!${RESET}"

## —— Tests —————————————————————————————————————————————————————————————————
test: ## Run all tests
	@docker compose exec php php bin/phpunit

test-unit: ## Run unit tests
	@docker compose exec php php bin/phpunit --testsuite=Unit

test-functional: ## Run functional tests
	@docker compose exec php php bin/phpunit --testsuite=Functional

test-coverage: ## Run tests with coverage
	@docker compose exec -e XDEBUG_MODE=coverage php php bin/phpunit --coverage-html var/coverage
	@echo "${GREEN}Coverage report generated in var/coverage/${RESET}"

## —— Quality ———————————————————————————————————————————————————————————————
qa: lint phpstan test ## Run all quality checks

lint: ## Run PHP CS Fixer in dry-run mode
	@docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style
	@docker compose exec php vendor/bin/php-cs-fixer fix

phpstan: ## Run PHPStan
	@docker compose exec php vendor/bin/phpstan analyse

security: ## Run security check
	@docker compose exec php composer audit

## —— CI/CD —————————————————————————————————————————————————————————————————
ci: ## Run CI checks locally
	@$(MAKE) lint
	@$(MAKE) phpstan
	@$(MAKE) test
	@echo "${GREEN}All CI checks passed!${RESET}"

build-prod: ## Build production image
	@docker build -t smartcafe-api:latest -f docker/php/Dockerfile .

## —— Database ——————————————————————————————————————————————————————————————
db-create: ## Create database
	@docker compose exec php php bin/console doctrine:database:create --if-not-exists

db-drop: ## Drop database
	@docker compose exec php php bin/console doctrine:database:drop --force --if-exists

db-reset: db-drop db-create migrate fixtures ## Reset database with fixtures

db-validate: ## Validate Doctrine schema
	@docker compose exec php php bin/console doctrine:schema:validate

## —— Clean —————————————————————————————————————————————————————————————————
clean: ## Clean generated files
	@rm -rf var/cache/* var/log/* .php-cs-fixer.cache .phpunit.cache
	@docker compose exec php php bin/console cache:clear
	@echo "${GREEN}Cleaned!${RESET}"

clean-all: stop ## Clean everything including containers
	@docker compose down -v --remove-orphans
	@rm -rf var/cache/* var/log/* vendor/ .php-cs-fixer.cache .phpunit.cache
	@echo "${GREEN}All cleaned!${RESET}"
