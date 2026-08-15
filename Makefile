.DEFAULT_GOAL := help

.PHONY: help up down logs reset-db test qa analyse cs-fix

help:
	@echo "SkillSpot development commands"
	@echo "  make up        Start the full development stack"
	@echo "  make reset-db  Recreate migrations and demo fixtures"
	@echo "  make qa        Run lint, style, PHPStan and PHPUnit"

up:
	docker compose up --wait

down:
	docker compose down --remove-orphans

logs:
	docker compose logs -f php worker scheduler

reset-db:
	docker compose exec php php bin/console doctrine:database:drop --if-exists --force
	docker compose exec php php bin/console doctrine:database:create
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

test:
	docker compose exec php php bin/phpunit

qa:
	docker compose exec php composer qa

analyse:
	docker compose exec php composer analyse

cs-fix:
	docker compose exec php php-cs-fixer fix --verbose
