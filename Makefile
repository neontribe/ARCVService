NAME = 192.168.21.97:5000/arcvouchers
#BRANCH = $(shell git rev-parse --abbrev-ref HEAD)
#VERSION = $(subst /,_,$(BRANCH))

build:
	docker build -t $(NAME)/service:develop --target=dev .
	docker build -t $(NAME)/service:prod .

build-no-cache:
	docker build -t $(NAME)/service:develop --target=dev --no-cache .
	docker build -t $(NAME)/service:prod --no-cache .

push:
	docker push $(NAME)/service:develop
	docker push $(NAME)/service:prod

release: build push

info:
	@echo Branch is $(BRANCH)
	@echo Version is $(VERSION)
