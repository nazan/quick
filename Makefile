DOCKER_REGISTRY ?= dkrhub:5000

UID ?= 1000

THIS_FILE := $(lastword $(MAKEFILE_LIST))


.PHONY: up
up:
	docker-compose -f ./dc/docker-compose.yml up -d

.PHONY: down
down:
	docker-compose -f ./dc/docker-compose.yml down

.PHONY: quick-db-prepare
quick-db-prepare:
	docker-compose exec mongodb /usr/local/my-setup/init-db.sh