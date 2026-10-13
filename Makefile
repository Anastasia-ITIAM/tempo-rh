DOCKER = docker compose exec
BACKEND = php

###> DOCKER
up:
	docker compose up -d

###> COMPOSER
install:
	$(DOCKER) $(BACKEND) composer install
update:
	$(DOCKER) $(BACKEND) composer update

###> DATABASE AND MIGRATIONS
db:
	$(DOCKER) $(BACKEND) php bin/console doctrine:database:create
migration:
	$(DOCKER) $(BACKEND) php bin/console make:migration
migrate:
	$(DOCKER) $(BACKEND) php bin/console doctrine:migrations:migrate
generate:
	$(DOCKER) $(BACKEND) php bin/console doctrine:migrations:generate

###> SYMFONY
%:
	$(DOCKER) $(BACKEND) php bin/console make:$@
cc:
	$(DOCKER) $(BACKEND) php bin/console cache:clear
debug-router:
	$(DOCKER) $(BACKEND) php bin/console debug:router
dump-autoload:
	$(DOCKER) $(BACKEND) composer dump-autoload

###> TOOLS
phpcs:
	$(DOCKER) $(BACKEND) ./vendor/bin/php-cs-fixer fix
phpstan:
	$(DOCKER) $(BACKEND) vendor/bin/phpstan analyse src
twigcs:
	$(DOCKER) $(BACKEND) vendor/bin/twig-cs-fixer fix templates

###> ASSET MAPPER
mapper-require:
	$(DOCKER) $(BACKEND) php bin/console importmap:require $VAR
compile:
	$(DOCKER) $(BACKEND) php bin/console asset-map:compile
mapper-install:
	$(DOCKER) $(BACKEND) php bin/console importmap:install
mapper-update:
	$(DOCKER) $(BACKEND) php bin/console importmap:update
debug-asset:
	$(DOCKER) $(BACKEND) php bin/console debug:asset-map

###> CUSTOM COMMANDS

###> EASYADMIN
ea-crud:
	$(DOCKER) $(BACKEND) php bin/console make:admin:crud
