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
test: ensure
	@if [ ! -f vendor/codecept.phar ]; then curl -sS -o vendor/codecept.phar https://raw.githubusercontent.com/Codeception/codeception.github.com/master/releases/2.0.14/codecept.phar; fi
	php vendor/codecept.phar run --debug
test-clean: test-ensure clean
	rm -rf $(WP_CORE_DIR)

# wordpress core install and config
wp-install: ensure wp-stop
	@if [ ! -f vendor/wp-cli.phar ]; then curl -sS -o vendor/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; fi
	@rm -rf wptest && mkdir wptest
	@php vendor/wp-cli.phar core download --path=wptest --version=$(WP_VERSION)
	@if [ -z "$(WP_TEST_DB_PASS)" ]; then \
		php vendor/wp-cli.phar core config --path=wptest --dbname=$(WP_TEST_DB_NAME) --dbuser=$(WP_TEST_DB_USER) ; \
	else \
		php vendor/wp-cli.phar core config --path=wptest --dbname=$(WP_TEST_DB_NAME) --dbuser=$(WP_TEST_DB_USER) --dbpass=$(WP_TEST_DB_PASS) ; \
	fi
	-@php vendor/wp-cli.phar db drop --path=wptest --yes
	-@php vendor/wp-cli.phar db create --path=wptest
	@php vendor/wp-cli.phar core install --path=wptest --url=http://127.0.0.1:4000 --title=PMPWPTests --admin_user=admin --admin_password=admin --admin_email=support@pmp.io
	@rm -f wptest/wp-content/plugins/pmp-wordpress && ln -s $$(pwd) wptest/wp-content/plugins/pmp-wordpress
	@php vendor/wp-cli.phar plugin activate pmp-wordpress --path=wptest
	@php vendor/wp-cli.phar option set pmp_settings '{"pmp_api_url":"$(PMP_API_URL)","pmp_client_id":"$(PMP_CLIENT_ID)","pmp_client_secret":"$(PMP_CLIENT_SECRET)"}' --format=json --path=wptest
wp-start:
	@if [ -f wptest/server.pid ] && ps -p $$(cat wptest/server.pid) > /dev/null 2>&1; then \
		echo "$$(tput setaf 2)Server already running on 127.0.0.1:4000$$(tput sgr0)"; \
	else \
		echo "$$(tput setaf 2)Listening on 127.0.0.1:4000$$(tput sgr0)" && rm -f wptest/server.log && rm -f wptest/server.pid; \
		php -S 127.0.0.1:4000 -t wptest > wptest/server.log 2>&1 & echo "$$!" > wptest/server.pid; \
	fi
	@if [ -f wptest/phantom.pid ] && ps -p $$(cat wptest/phantom.pid) > /dev/null 2>&1; then \
		echo "$$(tput setaf 2)Phantomjs already running on 4444$$(tput sgr0)"; \
	else \
		echo "$$(tput setaf 2)Phantomjs running on 4444$$(tput sgr0)" && rm -f wptest/phantom.log && rm -f wptest/phantom.pid; \
		phantomjs --webdriver=4444 --ssl-protocol=any --debug=true > wptest/phantom.log 2>&1 & echo "$$!" > wptest/phantom.pid; \
	fi
wp-stop:
	@if [ -f wptest/server.pid ] && ps -p $$(cat wptest/server.pid) > /dev/null 2>&1; \
	then echo "$$(tput setaf 2)Stopping server$$(tput sgr0)" && kill `cat wptest/server.pid`; \
	else echo "$$(tput setaf 2)Server not running$$(tput sgr0)"; \
	fi
	@if [ -f wptest/phantom.pid ] && ps -p $$(cat wptest/phantom.pid) > /dev/null 2>&1; \
	then echo "$$(tput setaf 2)Stopping phantomjs$$(tput sgr0)" && kill `cat wptest/phantom.pid`; \
	else echo "$$(tput setaf 2)Phantomjs not running$$(tput sgr0)"; \
	fi
	@rm -f wptest/server.pid
	@rm -f wptest/phantom.pid

# ensure we have a .env file
ifneq ($(strip $(wildcard .env)),)
include .env
endif
ensure:
	@if [ -z "$(WP_VERSION)" ];        then MISSING="$(MISSING) WP_VERSION"; fi ; \
	 if [ -z "$(WP_TEST_DB_NAME)" ];   then MISSING="$(MISSING) WP_TEST_DB_NAME"; fi ; \
	 if [ -z "$(WP_TEST_DB_USER)" ];   then MISSING="$(MISSING) WP_TEST_DB_USER"; fi ; \
	 if [ -z "$(PMP_API_URL)" ];       then MISSING="$(MISSING) PMP_API_URL"; fi ; \
	 if [ -z "$(PMP_CLIENT_ID)" ];     then MISSING="$(MISSING) PMP_CLIENT_ID"; fi ; \
	 if [ -z "$(PMP_CLIENT_SECRET)" ]; then MISSING="$(MISSING) PMP_CLIENT_SECRET"; fi ; \
	 if [ -n "$$MISSING" ]; then \
		echo "$$(tput setaf 1)Missing required env variables:$$(tput sgr0)$$MISSING - try using this .env file:"; \
		echo "" ; \
		echo "# pmp-wordpress test configurations" ; \
		echo "export WP_VERSION=4.1.1" ; \
		echo "export WP_TEST_DB_NAME=<<your_tmp_test_db_name>>" ; \
		echo "export WP_TEST_DB_USER=<<your_user_name>>" ; \
		echo "export WP_TEST_DB_PASS=<<your_user_password>>" ; \
		echo "export PMP_API_URL=https://api-sandbox.pmp.io" ; \
		echo "export PMP_CLIENT_ID=<<your_client_id>>" ; \
		echo "export PMP_CLIENT_SECRET=<<your_client_secret>>" ; \
		echo "" ; \
		exit 1; \
	fi
