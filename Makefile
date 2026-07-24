PROJECT_NAME := comunity-share-point
ZIP_NAME := $(PROJECT_NAME)-v3.zip

all: install zip

install:
	composer install # --no-dev --optimize-autoloader

zip:
	@echo "Creating $(ZIP_NAME) with root folder $(PROJECT_NAME)/"
	rm -rf /tmp/$(PROJECT_NAME)
	mkdir -p /tmp/$(PROJECT_NAME)
	rsync -a . /tmp/$(PROJECT_NAME) \
		--exclude=.env.dev \
		--exclude=.git \
		--exclude=.idea \
		--exclude=bin \
		--exclude=docker \
		--exclude=tests \
		--exclude=node_modules \
		--exclude=var/cache \
		--exclude=var/log \
		--exclude=$(ZIP_NAME)

	cd /tmp && zip -r $(ZIP_NAME) $(PROJECT_NAME)
	mv /tmp/$(ZIP_NAME) .
	rm -rf /tmp/$(PROJECT_NAME)

clean:
	rm -f $(ZIP_NAME)

.PHONY: all install zip clean

test-coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html var/tests-coverage
