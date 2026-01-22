.PHONY: help build up down restart logs shell db-create db-migrate db-seed test test-unit test-functional phpstan cs-check cs-fix qa

help: ## Affiche l'aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build les containers Docker
	docker-compose build

up: ## Démarre les containers
	docker-compose up -d

down: ## Arrête les containers
	docker-compose down

restart: down up ## Redémarre les containers

logs: ## Affiche les logs
	docker-compose logs -f

shell: ## Ouvre un shell dans le container PHP
	docker-compose exec php bash

db-create: ## Crée la base de données
	docker-compose exec php php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Exécute les migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

db-diff: ## Génère une migration
	docker-compose exec php php bin/console doctrine:migrations:diff

db-seed: ## Charge les fixtures
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

cache-clear: ## Vide le cache
	docker-compose exec php php bin/console cache:clear

composer-install: ## Installe les dépendances Composer
	docker-compose exec php composer install

jwt-generate: ## Génère les clés JWT
	docker-compose exec php php bin/console lexik:jwt:generate-keypair --overwrite

init: build up composer-install db-create db-migrate ## Initialise le projet complet

test: ## Lance tous les tests
	docker-compose exec php ./vendor/bin/phpunit

test-unit: ## Lance les tests unitaires
	docker-compose exec php ./vendor/bin/phpunit --testsuite=Unit

test-functional: ## Lance les tests fonctionnels
	docker-compose exec php ./vendor/bin/phpunit --testsuite=Functional

phpstan: ## Analyse statique du code
	docker-compose exec php ./vendor/bin/phpstan analyse

cs-check: ## Vérifie le style de code
	docker-compose exec php ./vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige le style de code
	docker-compose exec php ./vendor/bin/php-cs-fixer fix

qa: cs-check phpstan test ## Lance tous les checks qualité
