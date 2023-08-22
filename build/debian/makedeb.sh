#!/bin/bash
# Ripped from fossil makdedeb.sh

DEB_REV=${1-1} # .deb package build/revision number.
PACKAGE_DEBNAME=paheko
THISDIR=${PWD}

DEB_ARCH_NAME=all

PACKAGE_VERSION=`cat ../../src/VERSION`

[ ! -f ../paheko-${PACKAGE_VERSION}.tar.gz ] && (cd ../../src; make release)

tar xzvf ../paheko-${PACKAGE_VERSION}.tar.gz -C /tmp

SRCDIR="/tmp/paheko-${PACKAGE_VERSION}"

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
cp ${THISDIR}/paheko ${BINDIR}

mkdir -p "${DEBLOCALPREFIX}/share/menu"
cp ${THISDIR}/paheko.menu "${DEBLOCALPREFIX}/share/menu/paheko"
mkdir -p "${DEBLOCALPREFIX}/share/applications"
cp ${THISDIR}/paheko.desktop "${DEBLOCALPREFIX}/share/applications/"

CODEDIR=${DEBLOCALPREFIX}/share/${PACKAGE_DEBNAME}
mkdir -p ${CODEDIR}
cp -r ${SRCDIR}/* ${CODEDIR}
cp ${THISDIR}/config.debian.php ${CODEDIR}/config.local.php
mv ${CODEDIR}/data/plugins ${CODEDIR}/plugins
rm -rf ${CODEDIR}/*.sqlite ${CODEDIR}/data
cp ${THISDIR}/paheko.png "${CODEDIR}"

mkdir -p "${DEBROOT}/var/lib/${PACKAGE_DEBNAME}"
mkdir -p "${DEBROOT}/var/cache/${PACKAGE_DEBNAME}"
mkdir -p "${DEBROOT}/etc/${PACKAGE_DEBNAME}"

# Cleaning files that will be copied to /usr/share/doc
#rm -f ${CODEDIR}/../{README.md,COPYING}

cd $DEBROOT || {
    echo "Debian dest dir [$DEBROOT] not found. :("
    exit 2
}

rm -fr DEBIAN
mkdir DEBIAN

PACKAGE_DEB_VERSION=${PACKAGE_VERSION}
DEBFILE=${THISDIR}/${PACKAGE_DEBNAME}-${PACKAGE_DEB_VERSION}.deb
PACKAGE_TIME=$(/bin/date)

rm -f ${DEBFILE}
echo "Creating .deb package [${DEBFILE}]..."

echo "Generating md5 sums..."
find ${DEBLOCALPREFIX} -type f -exec md5sum {} \; > DEBIAN/md5sums

true && {
    echo "Generating Debian-specific files..."
    cp ${THISDIR}/../../COPYING ${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}/copyright
} || {
	echo "Fail."
	exit 1
}

true && {
    cat <<EOF > DEBIAN/postinst
#!/bin/sh

chown www-data:www-data /var/lib/paheko /var/cache/paheko
chown root:www-data /etc/paheko
chmod g=rX,o= /etc/paheko
chmod ug=rwX,o= /var/lib/paheko /var/cache/paheko
EOF

    chmod +x DEBIAN/postinst

}

true && {
    CHANGELOG=${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}/changelog.gz
    cat <<EOF | gzip -c > ${CHANGELOG}
${PACKAGE_DEBNAME} ${PACKAGE_DEB_VERSION}; urgency=low

This release has no changes over the core source distribution. It has
simply been Debianized.

Packaged by ${USER} <http://dev.kd2.org/paheko/> on
${PACKAGE_TIME}.

EOF

}

# doc.
DOCDIR=${DEBLOCALPREFIX}/share/doc/${PACKAGE_DEBNAME}

true && {
    echo "Generating doc..."
    cp ${THISDIR}/../../README.md ${DOCDIR}
    a2x --doctype manpage --format manpage ${THISDIR}/manpage.txt
    mkdir -p ${DEBLOCALPREFIX}/share/man/man1
    gzip -c ${THISDIR}/paheko.1 > ${DEBLOCALPREFIX}/share/man/man1/${PACKAGE_DEBNAME}.1.gz
    rm -f ${THISDIR}/paheko.1
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
Maintainer: Paheko <paheko@paheko.eu>
Architecture: ${DEB_ARCH_NAME}
Depends: dash | bash, php-cli (>=7.4), php-sqlite3, php-intl, php-mbstring, sensible-utils
Version: ${PACKAGE_DEB_VERSION}
Suggests: php-imagick
Replaces: garradin (<< 1.2.3~)
Breaks: garradin (<< 1.2.3~)
Homepage: https://fossil.kd2.org/paheko/
Description: Paheko is a tool to manage non-profit organizations.
 It's only available in french.
Description-fr: Gestionnaire d'association en interface web ou CLI.
 Paheko est un gestionnaire d'association à but non lucratif.
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
 Il y a également la possibilité de publier un site web simple,
 et un gestionnaire de documents permettant de gérer les fichiers de
 l'association.

EOF

}


true && {
    fakeroot dpkg-deb -b ${DEBROOT} ${DEBFILE}
    echo "Package file created:"
    ls -la ${DEBFILE}
    dpkg-deb --info ${DEBFILE}
}

cd - >/dev/null
true && {
    echo "Cleaning up..."
    rm -fr ${DEBROOT}
    rm -rf ${SRCDIR}
}

echo "Done :)"
