#!/bin/sh

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"

cd $SCRIPTPATH
cd ../src

find . -name '*.php' -exec php -l '{}' \; | fgrep -v 'No syntax error'
