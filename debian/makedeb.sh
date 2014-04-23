#!/bin/bash
# Ripped from fossil makdedeb.sh

DEB_REV=${1-1} # .deb package build/revision number.
PACKAGE_DEBNAME=garradin
THISDIR=${PWD}

DEB_ARCH_NAME=all

SRCDIR=$(cd ..; pwd)/src
test -e ${SRCDIR} || {
    echo "This script must be run from a BUILT copy of the source tree."
    exit 1
}

DEBROOT=$PWD/deb.tmp
test -d ${DEBROOT} && rm -fr ${DEBROOT}

DEBLOCALPREFIX=${DEBROOT}/usr
BINDIR=${DEBLOCALPREFIX}/bin
mkdir -p ${BINDIR}
mkdir -p ${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}
cp ${THISDIR}/garradin ${BINDIR}

CODEDIR=${DEBLOCALPREFIX}/share/${PACKAGE_DEBNAME}
mkdir -p ${CODEDIR}
cp -r ${SRCDIR}/* ${CODEDIR}
cp ${THISDIR}/config.debian.php ${CODEDIR}/config.local.php

# Cleaning files that will be copied to /usr/share/doc
rm -f ${CODEDIR}/{README,COPYING}

cd $DEBROOT || {
    echo "Debian dest dir [$DEBROOT] not found. :("
    exit 2
}

rm -fr DEBIAN
mkdir DEBIAN

PACKAGE_VERSION=`cat ${SRCDIR}/VERSION`
PACKAGE_DEB_VERSION=${PACKAGE_VERSION}-${DEB_REV}
DEBFILE=${THISDIR}/${PACKAGE_DEBNAME}-${PACKAGE_DEB_VERSION}-dev-${DEB_ARCH_NAME}.deb
PACKAGE_TIME=$(/bin/date)

rm -f ${DEBFILE}
echo "Creating .deb package [${DEBFILE}]..."

echo "Generating md5 sums..."
find ${DEBLOCALPREFIX} -type f -exec md5sum {} \; > DEBIAN/md5sums

true && {
    echo "Generating Debian-specific files..."
    cp ${SRCDIR}/COPYING ${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}/copyright
} || {
	echo "Fail."
	exit 1
}

true && {
    CHANGELOG=${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}/changelog.gz
    cat <<EOF | gzip -c > ${CHANGELOG}
${PACKAGE_DEBNAME} ${PACKAGE_DEB_VERSION}; urgency=low

This release has no changes over the core source distribution. It has
simply been Debianized.

Packaged by ${USER} <http://dev.kd2.org/garradin/> on
${PACKAGE_TIME}.

EOF

}

# doc.
DOCDIR=${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}

true && {
    echo "Generating doc..."
    cp ${SRCDIR}/README ${DOCDIR}
    a2x --doctype manpage --format manpage ${THISDIR}/manpage.txt
    gzip -c ${THISDIR}/garradin.1 > /usr/share/man/man1/${PACKAGE_DEBNAME}.1.gz
    rm -f ${THISDIR}/garradin.1
} || {
    echo "Fail."
    exit 1
}

true && {
    CONTROL=DEBIAN/control
    echo "Generating ${CONTROL}..."
    cat <<EOF > ${CONTROL}
Package: ${PACKAGE_DEBNAME}
Section: web
Priority: optional
Maintainer: Garradin <garradin@kd2.org>
Architecture: ${DEB_ARCH_NAME}
Depends: dash | bash, php5-cli (>=5.4), php5-sqlite
Version: ${PACKAGE_DEB_VERSION}
Suggests: www-browser
Homepage: http://garradin.kd2.org/
Description: Garradin is a tool to manage non-profit organizations.
 It's only available in french.
Description-fr: Gestionnaire d'association en interface web ou CLI.
 Garradin est un gestionnaire d'association à but non lucratif.
 Il permet de gérer les membres, leur adhésion et leurs contributions financières.
 Les membres peuvent se connecter eux-même et modifier leurs informations
 ou communiquer entre eux par e-mail. Une gestion précise des droits et
 autorisations est possible. Il est également possible de faire des
 envois de mails en groupe.
 .
 Un module de comptabilité à double entrée assure une gestion financière
 complète digne d'un vrai logiciel de comptabilité : suivi des opérations,
 graphiques, bilan annuel, compte de résultat, exercices, etc.
 .
 Un module wiki permet de prendre des notes de réunion, tenir à jour
 les informations internes à l'association (possibilité de chiffrer le
 contenu des pages) ou de publier des pages sur le site public intégré.
 L'aspect du site public peut être géré simplement avec ses squelettes
 SPIP.

EOF

}


true && {
    dpkg-deb -b ${DEBROOT} ${DEBFILE}
    echo "Package file created:"
    ls -la ${DEBFILE}
    dpkg-deb --info ${DEBFILE}
}

cd - >/dev/null
true && {
    echo "Cleaning up..."
    rm -fr ${DEBROOT}
}

echo "Done :)"
