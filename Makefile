#
# testing/building the plugin
#
.PHONY: install install-release clean pristene release
.PHONY: test test-clean
.PHONY: wp-install wp-start wp-stop
.PHONY: ensure

# basic commands
install:
	composer install
install-release: clean
	composer install --no-dev
clean:
	find vendor -maxdepth 1 -mindepth 1 ! -name 'pmpsdk.phar' -exec rm -rf {} +
	rm -rf wptest
pristine: clean
	rm -rf release
release:
	./release.sh

# test setup/running
test: ensure wp-start
	if [ ! -f vendor/codecept.phar ]; then curl -sS http://codeception.com/codecept.phar > vendor/codecept.phar; fi
	php vendor/codecept.phar run
test-clean: test-ensure clean
	rm -rf $(WP_CORE_DIR)

# wordpress core install and config
wp-install: ensure wp-stop
	@if [ ! -f vendor/wp-cli.phar ]; then curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar > vendor/wp-cli.phar; fi
	@rm -rf wptest && mkdir wptest
	@php vendor/wp-cli.phar core download --path=wptest --version=$(WP_VERSION)
	@php vendor/wp-cli.phar core config --path=wptest --dbname=$(WP_TEST_DB_NAME) --dbuser=$(WP_TEST_DB_USER) --dbpass=$(WP_TEST_DB_PASS)
	-@php vendor/wp-cli.phar db drop --path=wptest --yes
	-@php vendor/wp-cli.phar db create --path=wptest
	@php vendor/wp-cli.phar core install --path=wptest --url=http://localhost:4000 --title=PMPWPTests --admin_user=admin --admin_password=admin --admin_email=support@pmp.io
	@rm -f wptest/wp-content/plugins/pmp-wordpress && ln -s $$(pwd) wptest/wp-content/plugins/pmp-wordpress
	@php vendor/wp-cli.phar plugin activate pmp-wordpress --path=wptest
	@php vendor/wp-cli.phar option set pmp_settings '{"pmp_api_url":"$(PMP_API_URL)","pmp_client_id":"$(PMP_CLIENT_ID)","pmp_client_secret":"$(PMP_CLIENT_SECRET)"}' --format=json --path=wptest
wp-start:
	@if [ -f wptest/server.pid ] && ps -p $$(cat wptest/server.pid) > /dev/null 2>&1; \
	then \
		echo "$$(tput setaf 2)Server already running on localhost:4000$$(tput sgr0)"; \
	else \
		echo "$$(tput setaf 2)Listening on localhost:4000$$(tput sgr0)" && rm -f wptest/server.log && rm -f wptest/server.pid; \
		php -S localhost:4000 -t wptest > wptest/server.log 2>&1 & echo "$$!" > wptest/server.pid; \
	fi
wp-stop:
	@if [ -f wptest/server.pid ] && ps -p $$(cat wptest/server.pid) > /dev/null 2>&1; \
	then echo "$$(tput setaf 2)Stopping server$$(tput sgr0)" && kill `cat wptest/server.pid`; \
	else echo "$$(tput setaf 2)Server not running$$(tput sgr0)"; \
	fi
	@rm -f wptest/server.pid

# ensure we have a .env file
ifneq ($(strip $(wildcard .env)),)
include .env
endif
ensure:
	@if [ ! -f .env ]; then \
		echo "$$(tput setaf 1)Error: missing .env file!  Create one using the following example:$$(tput sgr0)\n" ; \
		echo "# pmp-wordpress test configurations" ; \
		echo "export WP_VERSION=4.1.1" ; \
		echo "export WP_TEST_DB_NAME=<<your_tmp_test_db_name>>" ; \
		echo "export WP_TEST_DB_USER=<<your_user_name>>" ; \
		echo "export WP_TEST_DB_PASS=<<your_user_password>>" ; \
		echo "export PMP_API_URL=https://api-sandbox.pmp.io" ; \
		echo "export PMP_CLIENT_ID=<<your_client_id>>" ; \
		echo "export PMP_CLIENT_SECRET=<<your_client_secret>>" ; \
		echo "" ; \
		exit 1 ; \
	fi;
