DOCKER=$(shell which docker)
DOCKER_COMPOSE=${DOCKER} compose

# Colors
G=\033[32m
Y=\033[33m
NC=\033[0m

##
## Help
## ----------------------
help: ## List of all commands
	@grep -E '(^[a-zA-Z_0-9-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
	| awk 'BEGIN {FS = ":.*?## "}; {printf "${G}%-24s${NC} %s\n", $$1, $$2}' \
	| sed -e 's/\[32m## /[33m/' && printf "\n";

.DEFAULT_GOAL := help
.PHONY: help

##
## Docker commands
## ----------------------
up: ## Up
	${DOCKER_COMPOSE} up -d

down: ## Stop and remove
	${DOCKER_COMPOSE} down

restart: down up ## Restart

build: ## Build docker
	${DOCKER_COMPOSE} build

db-inspect: ## Inspect DB server
	${DOCKER} inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' mariadb-develop
