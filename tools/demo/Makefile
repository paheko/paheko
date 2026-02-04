.PHONY: all clone sync update
SHELL := /bin/bash

DEMO_VERSION="trunk"
DEMO_PLUGINS_VERSION="trunk"
DEMO_MODULES_VERSION="trunk"

all:

clone:
	mkdir -p src
	cd src ; \
		fossil clone --nested https://fossil.kd2.org/paheko/; \
		fossil clone --nested https://fossil.kd2.org/kd2fw/; \
		fossil clone --nested https://fossil.kd2.org/paheko-plugins/; \
		fossil clone --nested https://fossil.kd2.org/paheko-modules/
	# Fix symlinks
	cd src/paheko && fossil settings allow-symlinks on && fossil revert

sync:
	cd src/paheko && fossil sync && fossil uv sync
	cd src/kd2fw && fossil sync
	cd src/paheko-plugins && fossil sync
	cd src/paheko-modules && fossil sync

update:
	cd src/paheko && fossil update ${DEMO_VERSION}
	cd src/paheko-plugins && fossil update ${DEMO_PLUGINS_VERSION}
	cd src/paheko-modules && fossil update ${DEMO_MODULES_VERSION}
	ln -sf ${PWD}/src/kd2fw/src/lib/KD2 src/paheko/src/include/lib/KD2
	ln -sf ${PWD}/config.paheko.php src/paheko/src/config.local.php
	ln -sf ${PWD}/src/modules src/paheko/src/modules
	rm -rf src/paheko/src/modules
