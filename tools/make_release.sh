#!/bin/sh

cd `dirname $0`
MY_PATH=$PWD
MY_DIR=`dirname ${MY_PATH}`

if [ ! -f "${MY_DIR}/src/VERSION" ]
then
    echo "${MY_DIR} n'est pas un répertoire de développement Garradin"
    exit 1
fi

#MANIFEST_VERSION=`cat manifest.uuid | cut -c1-10`
#DATE=`date +'%Y%m%d'`

VERSION=`cat ${MY_DIR}/src/VERSION`
TMPDIR=`mktemp -d`

cp -Lr ${MY_DIR}/src ${TMPDIR}/garradin-${VERSION} > /dev/null

cd ${TMPDIR}

mkdir ${TMPDIR}/garradin-${VERSION}/www/squelettes

tar cjvf "${MY_PATH}/garradin-${VERSION}.tar.bz2" --wildcards-match-slash \
    --exclude-vcs \
    --exclude '*/cache/compiled/*' \
    --exclude '*/cache/static/*' \
    --exclude '*.sqlite' \
    --exclude 'include/*/README' \
    --exclude 'include/*/COPYING' \
    --exclude '*.log' \
    --exclude 'plugins/*.gz' \
    --exclude 'config.local.php' \
    garradin-${VERSION}

rm -rf ${TMPDIR}
