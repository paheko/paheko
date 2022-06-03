#!/bin/sh

# Répertoire où sont stockées les données des utilisateurs
# veiller à ce que ce soit le même que dans config.local.php
FACTORY_USER_DIRECTORY="users"

# Chemin vers le script emails.php de Garradin
GARRADIN_CRON_SCRIPT="scripts/emails.php"

for user in $(cd ${FACTORY_USER_DIRECTORY} && ls -1d */)
do
	GARRADIN_FACTORY_USER=$(basename "$user") php $GARRADIN_CRON_SCRIPT
	echo $GARRADIN_FACTORY_USER
done
