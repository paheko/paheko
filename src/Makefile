KD2_FILE := https://fossil.kd2.org/kd2fw/uv/KD2-5.6.zip

deps:
	$(eval TMP_KD2=$(shell mktemp -d))
	#cd ${TMP_KD2}

	wget ${KD2_FILE} -O ${TMP_KD2}/kd2.zip

	rm -rf "include/lib/KD2"
	unzip "${TMP_KD2}/kd2.zip" -d include/lib

	rm -rf ${TMP_KD2}

dev-server:
	php -S localhost:8082 -t www www/_route.php

release:
	$(eval VERSION=$(shell cat VERSION))
	fossil ls | grep '^src/' | sed 's/src/garradin-${VERSION}/' > /tmp/garradin-${VERSION}-list.txt
	#echo "garradin-${VERSION}/include/lib/KD2" >> /tmp/garradin-${VERSION}-list.txt
	cat include/lib/dependencies.list | sed -r "s/^/garradin-${VERSION}\/include\/lib\//" >> /tmp/garradin-${VERSION}-list.txt
	rm -f /tmp/garradin-${VERSION}
	ln -s ${PWD} /tmp/garradin-${VERSION}
	tar cjvfh garradin-${VERSION}.tar.bz2 -C /tmp -T /tmp/garradin-${VERSION}-list.txt
	rm -f /tmp/garradin-${VERSION}*

publish: release
	$(eval VERSION=$(shell cat VERSION))
	fossil uv sync
	fossil uv ls | grep '^garradin-.*\.tar\.bz2' | xargs fossil uv rm
	fossil uv add garradin-${VERSION}.tar.bz2
	fossil uv sync

check-dependencies:
	grep -hEo '^use \\?KD2\\\w+|\\KD2\\\w+' -R include/lib/Garradin www | sed -r 's/^use \\?KD2\\|^\\KD2\\//' | sort | uniq