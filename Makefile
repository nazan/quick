DOCKER_REGISTRY ?= dkrhub:5000

UID ?= 1000

THIS_FILE := $(lastword $(MAKEFILE_LIST))


.PHONY: up
up:
	docker-compose -f ./dc/docker-compose.yml up -d

.PHONY: down
down:
	docker-compose -f ./dc/docker-compose.yml down





.PHONY: build
build:
	docker-compose -f ./dc/docker-compose.yml build quick-web
	docker-compose -f ./dc/docker-compose.yml build quick-ws
	docker-compose -f ./dc/docker-compose.yml build mongodb

#.PHONY: quick-db-prepare
#quick-db-prepare:
#	docker-compose -f ./dc/docker-compose.yml exec mongodb /usr/local/my-setup/init-db.sh

.PHONY: code-prepare
code-prepare:
	touch logs/app.log \
	&& chmod 777 logs/app.log templates/cache \
	&& mkdir -p odm/Hydrators odm/Proxies \
	&& chmod 777 odm/Hydrators odm/Proxies
	
	docker-compose -f ./dc/docker-compose.yml run -v "$$(pwd)":/home/appuser -w /home/appuser -u $(UID) --no-deps --rm quick-web composer install

	echo "Make sure config/application.ini exists and is set correctly. Also don't forget to update your /etc/hosts file."
