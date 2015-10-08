#
# testing/building the plugin
#
.PHONY: install install-release clean pristene release
.PHONY: test test-install test-clean test-ensure

# basic commands
install:
	composer install
	bower install
install-release: clean
	composer install --no-dev
	bower install --production
clean:
	find vendor -maxdepth 1 -mindepth 1 ! -name 'pmpsdk.phar' -exec rm -rf {} +
pristine: clean test-clean
	rm -rf release
release:
	./release.sh

# test setup/running (requires .env file)
ifneq ($(strip $(wildcard .env)),)
include .env
endif
test: test-ensure test-install
	WP_TESTS_DIR=$(WP_CORE_DIR)/tests/phpunit/ ./vendor/bin/phpunit
test-install: test-ensure install
	composer install
	@if [ -d "$(WP_CORE_DIR)" ]; then \
		echo "Using wordpress v$(WP_VERSION)" ; \
	else \
		echo "Checking out wordpress v$(WP_VERSION) ..." ; \
		svn checkout --quiet http://develop.svn.wordpress.org/tags/$(WP_VERSION) $(WP_CORE_DIR) ; \
	fi;
	rm -f $(WP_CORE_DIR)/src/wp-content/plugins/$(WP_PLUGIN_SLUG) && ln -s $$(pwd) $(WP_CORE_DIR)/src/wp-content/plugins/$(WP_PLUGIN_SLUG)
	cp $(WP_CORE_DIR)/wp-tests-config-sample.php wp-tests-config.php
	sed "s:dirname( __FILE__ ) . '/src/':'$$(cd $(WP_CORE_DIR) && pwd)/src/':" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/youremptytestdbnamehere/$(WP_TEST_DB_NAME)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/yourusernamehere/$(WP_TEST_DB_USER)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/yourpasswordhere/$(WP_TEST_DB_PASS)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	mv wp-tests-config.php "$(WP_CORE_DIR)/tests/phpunit/wp-tests-config.php"
test-clean: test-ensure clean
	rm -rf $(WP_CORE_DIR)
test-ensure:
	@if [ ! -f .env ]; then \
		echo "Error: missing .env file!  Create one using the following example:\n" ; \
		echo "# pmp-wordpress test configurations" ; \
		echo "export WP_VERSION=4.1.1" ; \
		echo "export WP_CORE_DIR=wptest" ; \
		echo "export WP_PLUGIN_SLUG=pmp-wordpress" ; \
		echo "export WP_TEST_DB_NAME=<<your_tmp_test_db_name>>" ; \
		echo "export WP_TEST_DB_USER=<<your_user_name>>" ; \
		echo "export WP_TEST_DB_PASS=<<your_user_password>>" ; \
		echo "export PMP_API_URL=https://api-sandbox.pmp.io" ; \
		echo "export PMP_CLIENT_ID=<<your_client_id>>" ; \
		echo "export PMP_CLIENT_SECRET=<<your_client_secret>>" ; \
		echo "" ; \
		exit 1 ; \
	fi;
