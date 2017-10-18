#!/bin/sh

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"

if [ -f $SCRIPTPATH/config.sh ]
then
    . $SCRIPTPATH/config.sh
fi

if [ "$1" = "--reset" ]
then
    rm -rf $TESTDIR
fi

if [ ! -d $TESTDIR ]
then
    mkdir -p $TESTDIR
    cd $TESTDIR
    fossil clone https://fossil.kd2.org/garradin/ garradin.fossil
    fossil open garradin.fossil
    sh tools/setup_dependencies.sh
    cd $SCRIPTPATH
fi

if [ "$1" != "--no-install" ]
then
	rm -rf $TESTDIR/src/cache
	rm -rf $TESTDIR/src/*.sqlite
fi

php -S localhost:8080 -t $TESTDIR/src/www &
PHP_PID=$!

if [ "$1" != "--no-install" ]
then
	$CASPERJS test 0*.js
fi

$CASPERJS --cookies-file=$TESTDIR/cookies.txt test 1*.js

kill $!
