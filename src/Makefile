.PHONY: dev-server release deps publish check-dependencies test minify phpstan www htaccess modules
KD2_FILE := https://fossil.kd2.org/kd2fw/uv/KD2-7.4.zip
MODULES_FILE := https://fossil.kd2.org/paheko-modules/zip/trunk/modules.zip

deps:
	$(eval TMP_KD2=$(shell mktemp -d))
	#cd ${TMP_KD2}

	wget ${KD2_FILE} -O ${TMP_KD2}/kd2.zip

	rm -rf "include/lib/KD2"
	unzip "${TMP_KD2}/kd2.zip" -d include/lib

	rm -rf ${TMP_KD2}

modules:
	wget ${MODULES_FILE} -O modules.zip
	unzip modules.zip
	rm -f modules.zip

dev-server:
	php -S localhost:8082 -d upload_max_filesize=256M -d post_max_size=256M -t www www/_route.php

test:
	find . -name '*.php' -not -path './data/*' -print0 | xargs -0 -n1 php -l > /dev/null

phpstan:
	phpstan.phar analyze -c ../tests/phpstan.neon include www

psalm:
	@# This is required by psalm, but useless
	@-mkdir vendor
	@-echo '{"require": {}}' > vendor/autoload.php
	psalm.phar -c ../tests/psalm.xml

doc:
	php ../tools/doc_md_to_html.php

htaccess:
	cat apache-vhost.conf \
		| sed 's/#RewriteBase/RewriteBase/' \
		| sed 's/RewriteCond %{DOCUMENT_ROOT}%{REQUEST_/RewriteCond %{REQUEST_/' \
		> www/.htaccess
	cat apache-htaccess.conf >> www/.htaccess

release: test minify doc
	$(eval VERSION=$(shell cat VERSION))
	rm -rf /tmp/paheko-build
	mkdir -p /tmp/paheko-build
	fossil zip ${VERSION} /tmp/paheko-build/src.zip --name paheko
	unzip -d /tmp/paheko-build /tmp/paheko-build/src.zip
	cd include/lib; \
		rsync --files-from=dependencies.list -r ./ /tmp/paheko-build/paheko/src/include/lib/
	mv www/admin/static/mini.css /tmp/paheko-build/paheko/src/www/admin/static/admin.css
	# Generate .htaccess file
	cd /tmp/paheko-build/paheko/src && make htaccess
	cd /tmp/paheko-build/paheko/src/www/admin/static; \
		rm -f font/*.css font/*.json
	cd /tmp/paheko-build/paheko/src; \
		rm -f Makefile include/lib/KD2/data/countries.en.json

	# Download modules and only keep the stable ones
	cd /tmp/paheko-build/paheko/src && \
		wget https://fossil.kd2.org/paheko-modules/zip/trunk/modules.zip && \
		unzip -o modules.zip && \
		rm -rf `find modules/ -name 'ignore' -type f -execdir pwd \;` && \
		rm -f modules.zip

	# Download plugins and only keep the stable ones
	cd /tmp/paheko-build/paheko/src/data && \
		wget https://fossil.kd2.org/paheko-plugins/zip/dev/plugins.zip && \
		unzip -o plugins.zip && \
		rm -rf `find plugins/ -name 'ignore' -type f -execdir pwd \;` && \
		rm -f plugins.zip

	mv /tmp/paheko-build/paheko/src /tmp/paheko-build/paheko-${VERSION}
	tar czvfh ../build/paheko-${VERSION}.tar.gz --hard-dereference -C /tmp/paheko-build paheko-${VERSION}

deb:
	cd ../build/debian; ./makedeb.sh

windows:
	cd ../build/windows; make installer

publish: release deb windows
	$(eval VERSION=$(shell cat VERSION))
	cd ../build && gpg --armor -u dev@paheko.cloud --detach-sign paheko-${VERSION}.tar.gz
	fossil uv sync
	#fossil uv ls | fgrep -v 'paheko-0.8.5' | grep '^paheko-.*\.(tar\.bz2|deb)' | xargs fossil uv rm
	cd ../build && \
		fossil uv add paheko-${VERSION}.tar.gz && \
		fossil uv add paheko-${VERSION}.tar.gz.asc
	cd ../build/debian && fossil uv add paheko-${VERSION}.deb
	cd ../tools && php make_installer.php > install.php && fossil uv add install.php && rm install.php
	fossil uv sync
	cd ../build/windows && make publish

check-dependencies:
	grep -hEo '^use \\?KD2\\[^; ]+|\\KD2\\[^\(:; ]+' -R include/lib/Garradin www | sed -r 's/^use \\?KD2\\|^\\KD2\\//' | sort | uniq

minify:
	cat `ls www/admin/static/styles/[0-9]*.css` | sed 's/\.\.\///' > www/admin/static/mini.css
	@# Minify is only gaining 500 gzipped bytes (4kB uncompressed) but making things hard to read/hack
	@#yui-compressor --nomunge www/admin/static/mini.css -o www/admin/static/mini.css
