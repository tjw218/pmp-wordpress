#
# testing/building the plugin
#

# load the environment file, if it exists
HAVE_ENV := $(wildcard .env)
ifneq ($(strip $(HAVE_ENV)),)
include .env
endif

# bundle a zip file for release to wordpress.org
build: clean
	@mkdir -p build
	composer install --no-dev
	zip -r build/wp-release.zip . --exclude ".*" "vendor/pmpsdk.phar" "build/*" "composer.*" "tests/*" "phpunit.xml" "Makefile" -q

# run phpunit tests (run install first)
test: ensure
	WP_TESTS_DIR=$(WP_CORE_DIR)/tests/phpunit/ ./vendor/bin/phpunit

# remove temporary directories
clean:
	rm -rf build
	rm -rf wptest
	find vendor -maxdepth 1 -mindepth 1 ! -name 'pmpsdk.phar' -exec rm -rf {} +

# install wordpress core to run tests (should work on osx or *nix systems)
install: ensure
	composer install
	@if [ -d "$(WP_CORE_DIR)" ]; then \
		echo "Using wordpress v$(WP_VERSION)" ; \
	else \
		echo "Checking out wordpress v$(WP_VERSION) ..." ; \
		svn checkout --quiet http://develop.svn.wordpress.org/tags/$(WP_VERSION) $(WP_CORE_DIR) ; \
	fi;
	rm -f $(WP_CORE_DIR)/src/wp-content/plugins/pmp-wordpress && ln -s $$(pwd) $(WP_CORE_DIR)/src/wp-content/plugins/pmp-wordpress
	cp $(WP_CORE_DIR)/wp-tests-config-sample.php wp-tests-config.php
	sed "s:dirname( __FILE__ ) . '/src/':'$$(cd $(WP_CORE_DIR) && pwd)/src/':" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/youremptytestdbnamehere/$(WP_TEST_DB_NAME)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/yourusernamehere/$(WP_TEST_DB_USER)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	sed "s/yourpasswordhere/$(WP_TEST_DB_PASS)/" wp-tests-config.php > wp-tests-config.php.new && mv wp-tests-config.php.new wp-tests-config.php
	mv wp-tests-config.php "$(WP_CORE_DIR)/tests/phpunit/wp-tests-config.php"

# make sure you've created an .env file
ensure:
	@if [ ! -f .env ]; then \
		echo "Error: missing .env file!  Create one using the following example:\n" ; \
		echo "# pmp-wordpress test configurations" ; \
		echo "export WP_VERSION=4.1.1" ; \
		echo "export WP_CORE_DIR=wptest" ; \
		echo "export WP_TEST_DB_NAME=<<your_tmp_test_db_name>>" ; \
		echo "export WP_TEST_DB_USER=<<your_user_name>>" ; \
		echo "export WP_TEST_DB_PASS=<<your_user_password>>" ; \
		echo "export PMP_API_URL=https://api-sandbox.pmp.io" ; \
		echo "export PMP_CLIENT_ID=<<your_client_id>>" ; \
		echo "export PMP_CLIENT_SECRET=<<your_client_secret>>" ; \
		echo "" ; \
		exit 1 ; \
	fi;

.PHONY: install test build clean ensure
