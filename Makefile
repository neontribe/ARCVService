NAME = dockerhost.neontribe.net:5000/arc
BRANCH = $(shell git rev-parse --abbrev-ref HEAD)
VERSION = $(subst /,_,$(BRANCH))

test:
	env NAME=$(NAME) VERSION=$(VERSION) bats .docker/tests.bats

build:
	docker build -t $(NAME):$(VERSION) -f .docker/Dockerfile --rm .

build-nocache:
	docker build -t $(NAME):$(VERSION) -f .docker/Dockerfile --rm --no-cache .

tag-latest:
	docker tag $(NAME):$(VERSION) $(NAME):latest

push:
	docker push $(NAME):$(VERSION)

release: build test tag-latest push

info:
	@echo Branch is $(BRANCH)
	@echo Version is $(VERSION)
