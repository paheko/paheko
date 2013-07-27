#!/bin/sh

# Mise en place/à jour des dépendances

if [ ! -f include/libs ]
then
	echo "Doit être invoqué à la racine du repository"
	exit 0
fi

KEYWORD="checkout"

# Template Lite
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/template_lite/ include/libs/template_lite/

# Liste des pays
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/i18n/countries/ include/libs/countries/

# Passphrase
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/i18n/passphrase/ include/libs/passphrase/

# Garbage2xhtml
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/garbage2xhtml/ include/libs/garbage2xhtml/

# MiniSkel
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/miniskel/ include/libs/miniskel/

# Diff
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/diff/ include/libs/diff/

# SVGPlot
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/svgplot/ include/libs/svgplot
