.PHONY: info install up down test test-coverage phpstan ci logs build push build-ci-image test-ci phpstan-ci
.ONESHELL:

PROJECT_NAME := simpsons-quotes-api
APP_SERVICE := app
LOCAL_DEV_IMAGE := $(PROJECT_NAME)-dev:latest
AWS_REGION ?= eu-central-1
AWS_ACCOUNT_ID ?= $(shell aws sts get-caller-identity --query Account --output text 2>/dev/null || true)
GIT_REV ?= $(shell git log -1 --pretty=format:"%h" 2>/dev/null || echo "n/a")
GIT_BRANCH ?= $(shell git symbolic-ref --short HEAD 2>/dev/null || git rev-parse --short HEAD 2>/dev/null || echo "n/a")
BUILD_NUMBER ?= 1
DOCKER_REPO_NAME ?= slin-master/$(PROJECT_NAME)
DOCKER_REPO_URL ?= $(if $(AWS_ACCOUNT_ID),$(AWS_ACCOUNT_ID).dkr.ecr.$(AWS_REGION).amazonaws.com)
DOCKER_REPO_URI ?= $(if $(DOCKER_REPO_URL),$(DOCKER_REPO_URL)/$(DOCKER_REPO_NAME),$(DOCKER_REPO_NAME))
DOCKER_TAG := latest
DOCKER_TAG_REV := $(GIT_REV)
DOCKER_TAG_CI := $(shell echo "$(GIT_BRANCH)" | tr '/_' '--')-$(BUILD_NUMBER)
TEST_ENV_ARGS := -e APP_ENV=testing -e APP_DEBUG=true -e DB_CONNECTION=sqlite -e DB_DATABASE=storage/testing.sqlite -e CACHE_STORE=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync

default: info

info:
	@echo "PROJECT...............: $(PROJECT_NAME)"
	@echo "AWS REGION............: $(AWS_REGION)"
	@echo "AWS ACCOUNT ID........: $(if $(AWS_ACCOUNT_ID),$(AWS_ACCOUNT_ID),not resolved)"
	@echo "GIT REVISION..........: $(GIT_REV)"
	@echo "GIT BRANCH............: $(GIT_BRANCH)"
	@echo "BUILD NUMBER..........: $(BUILD_NUMBER)"
	@echo "DOCKER REPOSITORY.....: $(DOCKER_REPO_URI)"
	@echo "DOCKER TAG (LATEST)...: $(DOCKER_TAG)"
	@echo "DOCKER TAG (REV)......: $(DOCKER_TAG_REV)"
	@echo "DOCKER TAG (CI).......: $(DOCKER_TAG_CI)"

install:
	docker run --rm -u $$(id -u):$$(id -g) -v "$$PWD":/app -w /app composer:2 composer install

up:
	docker compose up --build -d

down:
	docker compose down

test:
	IMAGE_NAME=$(LOCAL_DEV_IMAGE) DOCKER_BUILD_TARGET=development docker compose run --rm -T -e XDEBUG_MODE=off $(TEST_ENV_ARGS) $(APP_SERVICE) sh -lc 'cd /var/www/html && rm -f storage/testing.sqlite && touch storage/testing.sqlite && php artisan test --ansi'

test-coverage:
	mkdir -p storage/test-reports
	IMAGE_NAME=$(LOCAL_DEV_IMAGE) DOCKER_BUILD_TARGET=development docker compose run --rm -T -e XDEBUG_MODE=coverage $(TEST_ENV_ARGS) -v "$$(pwd)/storage/test-reports:/var/www/html/storage/test-reports" $(APP_SERVICE) sh -lc 'cd /var/www/html && rm -f storage/testing.sqlite && touch storage/testing.sqlite && php artisan test --ansi --coverage-clover=storage/test-reports/phpunit.coverage.xml --coverage-html=storage/test-reports/report --log-junit=storage/test-reports/phpunit.xml'

phpstan:
	IMAGE_NAME=$(LOCAL_DEV_IMAGE) DOCKER_BUILD_TARGET=development docker compose run --rm -T -e XDEBUG_MODE=off $(APP_SERVICE) sh -lc 'cd /var/www/html && php ./vendor/bin/phpstan analyse --ansi --no-progress --memory-limit=1G'

build-ci-image:
	IMAGE_NAME=$(LOCAL_DEV_IMAGE) DOCKER_BUILD_TARGET=development docker compose build $(APP_SERVICE)

test-ci: build-ci-image test-coverage

phpstan-ci: build-ci-image phpstan

ci: test-ci phpstan-ci

logs:
	docker compose logs -f $(APP_SERVICE)

assert-ecr-config:
	@if [ -z "$(AWS_ACCOUNT_ID)" ]; then \
		echo "AWS account ID could not be resolved. Configure AWS credentials or set AWS_ACCOUNT_ID/DOCKER_REPO_URL explicitly."; \
		exit 1; \
	fi

build:
	$(MAKE) assert-ecr-config
	docker pull -q $(DOCKER_REPO_URI):latest >/dev/null 2>&1 || true
	DOCKER_BUILDKIT=1 docker build --build-arg BUILDKIT_INLINE_CACHE=1 --cache-from $(DOCKER_REPO_URI):latest --target production -t $(DOCKER_REPO_URI):$(DOCKER_TAG) -f docker/app/Dockerfile .

push:
	$(MAKE) assert-ecr-config
	docker tag $(DOCKER_REPO_URI):$(DOCKER_TAG) $(DOCKER_REPO_URI):$(DOCKER_TAG_REV)
	docker push -q $(DOCKER_REPO_URI):$(DOCKER_TAG_REV)
	docker tag $(DOCKER_REPO_URI):$(DOCKER_TAG) $(DOCKER_REPO_URI):$(DOCKER_TAG_CI)
	docker push -q $(DOCKER_REPO_URI):$(DOCKER_TAG_CI)
	if [ "$(GIT_BRANCH)" = "master" ]; then \
		docker push -q $(DOCKER_REPO_URI):$(DOCKER_TAG); \
	fi
