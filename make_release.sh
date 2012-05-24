#!/bin/sh

cd `dirname $0`
MY_PATH=$PWD
MY_DIR=`basename ${MY_PATH}`

MANIFEST_VERSION=`cat manifest.uuid | cut -c1-10`
VERSION=`cat VERSION`
DATE=`date +'%Y%m%d'`

cd `dirname ${MY_PATH}`

# FIXME TODO exclure des libs ce qui n'est pas utilisé par l'appli (démos, README, etc.)

tar cjvf "${MY_DIR}-${VERSION}-${MANIFEST_VERSION}-${DATE}.tar.bz2" --wildcards-match-slash \
    --exclude-vcs \
    --exclude '*/compiled/*' \
    --exclude '*.fossil' \
    --exclude '_FOSSIL_' \
    --exclude 'manifest' \
    --exclude '*.db' \
    --exclude 'doc' \
    --exclude 'test*' \
    --exclude '*.sh' \
    --exclude 'squelettes/*' \
    --exclude 'www/elements/*' \
    ${MY_DIR}

cd ${MY_PATH}
