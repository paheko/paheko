#!/bin/sh

# Répertoire où sont stockées les données des utilisateurs
# veiller à ce que ce soit le même que dans config.local.php
FACTORY_USER_DIRECTORY="users"

# Chemin vers le script bin/paheko
PAHEKO_BIN="bin/paheko"

for user in $(cd ${FACTORY_USER_DIRECTORY} && ls -1d */)
do
	PAHEKO_FACTORY_USER=$(basename "$user") php $PAHEKO_BIN cron
	echo $PAHEKO_FACTORY_USER
done
