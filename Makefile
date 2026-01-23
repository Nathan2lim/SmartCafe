.PHONY: help build up down restart logs shell db-create db-migrate db-seed test test-unit test-functional phpstan cs-check cs-fix qa admin-install admin-dev admin-build admin-shell prod-up prod-down prod-logs

# =============================================================================
# HELP
# =============================================================================
help: ## Affiche l'aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# =============================================================================
# DOCKER - Development
# =============================================================================
build: ## Build les containers Docker (dev)
	docker-compose build

up: ## Démarre les containers (dev)
	docker-compose up -d

down: ## Arrête les containers (dev)
	docker-compose down

restart: down up ## Redémarre les containers (dev)

logs: ## Affiche les logs de tous les containers
	docker-compose logs -f

logs-api: ## Affiche les logs du backend
	docker-compose logs -f php nginx

logs-admin: ## Affiche les logs du frontend admin
	docker-compose logs -f admin

shell: ## Ouvre un shell dans le container PHP
	docker-compose exec php bash

# =============================================================================
# DOCKER - Production
# =============================================================================
prod-build: ## Build les containers Docker (prod)
	docker-compose -f docker-compose.prod.yml build

prod-up: ## Démarre les containers (prod)
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Arrête les containers (prod)
	docker-compose -f docker-compose.prod.yml down

prod-logs: ## Affiche les logs (prod)
	docker-compose -f docker-compose.prod.yml logs -f

prod-deploy: prod-build prod-up db-migrate-prod cache-clear-prod ## Déploie en production

db-migrate-prod: ## Exécute les migrations (prod)
	docker-compose -f docker-compose.prod.yml exec -T php php bin/console doctrine:migrations:migrate --no-interaction

cache-clear-prod: ## Vide le cache (prod)
	docker-compose -f docker-compose.prod.yml exec -T php php bin/console cache:clear --env=prod

# =============================================================================
# DATABASE
# =============================================================================
db-create: ## Crée la base de données
	docker-compose exec php php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Exécute les migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

db-diff: ## Génère une migration
	docker-compose exec php php bin/console doctrine:migrations:diff

db-seed: ## Charge les fixtures
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

db-reset: ## Reset complet de la base
	docker-compose exec php php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec php php bin/console doctrine:database:create
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

# =============================================================================
# BACKEND (API)
# =============================================================================
cache-clear: ## Vide le cache
	docker-compose exec php php bin/console cache:clear

composer-install: ## Installe les dépendances Composer
	docker-compose exec php composer install

jwt-generate: ## Génère les clés JWT
	docker-compose exec php php bin/console lexik:jwt:generate-keypair --overwrite

# =============================================================================
# FRONTEND (Admin)
# =============================================================================
admin-install: ## Installe les dépendances npm du frontend
	cd admin && npm install

admin-dev: ## Lance le serveur de développement frontend
	cd admin && npm run dev

admin-build: ## Build le frontend pour la production
	cd admin && npm run build

admin-shell: ## Ouvre un shell dans le container admin
	docker-compose exec admin sh

admin-logs: ## Affiche les logs du frontend
	docker-compose logs -f admin

# =============================================================================
# TESTS & QA
# =============================================================================
test: ## Lance tous les tests backend
	docker-compose exec php ./vendor/bin/phpunit

test-unit: ## Lance les tests unitaires
	docker-compose exec php ./vendor/bin/phpunit --testsuite=Unit

test-functional: ## Lance les tests fonctionnels
	docker-compose exec php ./vendor/bin/phpunit --testsuite=Functional

phpstan: ## Analyse statique du code PHP
	docker-compose exec php ./vendor/bin/phpstan analyse

cs-check: ## Vérifie le style de code PHP
	docker-compose exec php ./vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige le style de code PHP
	docker-compose exec php ./vendor/bin/php-cs-fixer fix

qa: cs-check phpstan test ## Lance tous les checks qualité backend

qa-frontend: admin-build ## Lance les checks qualité frontend (type check + build)

qa-all: qa qa-frontend ## Lance tous les checks qualité (backend + frontend)

# =============================================================================
# INITIALIZATION
# =============================================================================
init: build up composer-install jwt-generate db-create db-migrate db-seed ## Initialise le projet complet (backend)
	@echo "✅ Backend initialisé avec succès!"
	@echo "   API disponible sur: http://localhost:8080/api"

init-admin: admin-install ## Initialise le frontend
	@echo "✅ Frontend initialisé avec succès!"
	@echo "   Lancez 'make admin-dev' pour démarrer le serveur de développement"

init-all: init init-admin ## Initialise tout le projet (backend + frontend)
	@echo ""
	@echo "🚀 SmartCafe est prêt!"
	@echo "   Backend API:  http://localhost:8080/api"
	@echo "   Frontend:     http://localhost:5173 (après 'make admin-dev')"
