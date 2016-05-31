#!/bin/sh

# Mise en place/à jour des dépendances

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"

if [ ! -d "$SCRIPTPATH/../src" ]
then
	echo "$SCRIPTPATH n'est pas le bon chemin, ou répertoire src/ manquant"
	exit 0
fi

KEYWORD="checkout"
SRCPATH="$SCRIPTPATH/../src/include/lib"

# Template Lite
svn ${KEYWORD} https://svn.kd2.org/svn/misc/libs/template_lite/ "$SRCPATH/Template_Lite/"

dir=`mktemp -d` && cd $dir

wget https://fossil.kd2.org/kd2fw/zip/KD2+Framework-trunk.zip
unzip "KD2+Framework-trunk.zip"

mv "KD2 Framework-trunk/src/lib/kd2" "$SRCPATH/KD2"

cd "$SRCPATH"

rm -rf "$dir"