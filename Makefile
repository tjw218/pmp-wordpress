#
# testing/building the plugin
#

install:
	composer install

test: install ensure
	source .env && ./vendor/bin/phpunit

build: clean
	@mkdir -p build
	composer install --no-dev
	zip -r build/wp-release.zip . --exclude ".*" "vendor/pmpsdk.phar" "build/*" "composer.*" "tests/*" "phpunit.xml" "Makefile" -q

clean:
	rm -rf build
	find vendor -maxdepth 1 -mindepth 1 ! -name 'pmpsdk.phar' -exec rm -rf {} +

ensure:
	@if [ -f .env ]; then \
	  source .env ; \
	else \
	  echo "Error: missing .env file!  Create one using the following example:\n" ; \
	  echo '# pmp-wordpress test configurations' ; \
	  echo "export WP_CORE_DIR=/tmp/wordpress" ; \
	  echo 'export WP_TESTS_DIR=$$WP_CORE_DIR/tests/phpunit' ; \
	 	echo "export PMP_API_URL=https://api-sandbox.pmp.io" ; \
	 	echo "export PMP_CLIENT_ID=1234" ; \
	 	echo "export PMP_CLIENT_SECRET=5678" ; \
	 	echo "" ; \
	 	exit 1 ; \
	fi;

.PHONY: install test build clean ensure
