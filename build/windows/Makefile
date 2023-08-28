.PHONY := php installer clean publish
PHP_ARCHIVE := https://windows.php.net/downloads/releases/php-8.0.28-nts-Win32-vs16-x64.zip

all: installer

php.zip:
	wget ${PHP_ARCHIVE} -O php.zip

php: php.zip
	mkdir -p install_dir/php
	unzip -o php.zip -d install_dir/php > /dev/null

	# Remove unused files
	@cd install_dir/php && rm -rf \
		phpdbg.exe \
		php8phpdbg.dll \
		php8embed.lib \
		php-cgi.exe \
		php.ini-* \
		dev \
		phar* \
		nghttp2.dll \
		libpq.dll \
		libenchant

	# Remove unused extensions
	@cd install_dir/php/ext && rm -f \
		php_bz2.dll \
		php_com_dotnet.dll \
		php_curl.dll \
		php_dba.dll \
		php_dl_test.dll \
		php_enchant.dll \
		php_exif.dll \
		php_ffi.dll \
		php_ftp.dll \
		php_gmp.dll \
		php_imap.dll \
		php_ldap.dll \
		php_mysqli.dll \
		php_oci8_19.dll \
		php_odbc.dll \
		php_opcache.dll \
		php_pdo_firebird.dll \
		php_pdo_mysql.dll \
		php_pdo_oci.dll \
		php_pdo_odbc.dll \
		php_pdo_pgsql.dll \
		php_pdo_sqlite.dll \
		php_pgsql.dll \
		php_shmop.dll \
		php_snmp.dll \
		php_soap.dll \
		php_sysvshm.dll \
		php_xsl.dll \
		php_zend_test.dll

	du -hs install_dir/php

installer: clean php
	$(eval VERSION=$(shell cat ../../src/VERSION))
	# NSIS only accepts numbers as version
	$(eval NSIS_VERSION=$(shell sed -E 's/-(alpha|beta|rc)[0-9]+//' ../../src/VERSION))
	mkdir -p install_dir
	cp ../paheko-${VERSION}.tar.gz install_dir/
	cd install_dir && tar xzf paheko-${VERSION}.tar.gz && mv paheko-${VERSION} paheko
	cp config.local.php install_dir/paheko/
	cp php.ini install_dir/php
	cp launch.bat install_dir
	cp paheko.ico install_dir
	rm -f install_dir/paheko-${VERSION}.tar.gz
	makensis -V3 -DNVERSION=${NSIS_VERSION} -DVERSION=${VERSION} paheko.nsis

clean:
	rm -rf install_dir

publish:
	$(eval VERSION=$(shell cat ../../src/VERSION))
	fossil uv ls | grep '^paheko-.*\.exe' | xargs fossil uv rm
	fossil uv add paheko-${VERSION}.exe
	fossil uv sync
