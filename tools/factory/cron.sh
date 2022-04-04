#!/bin/sh

# Répertoire où sont stockées les données des utilisateurs
# veiller à ce que ce soit le même que dans config.local.php
USINE_USER_DIRECTORY="/.../garradin/users"

# Chemin vers le script cron.php de Garradin
GARRADIN_CRON_SCRIPT="/.../scripts/cron.php"

for user in $(cd ${USINE_USER_DIRECTORY} && ls -1d */)
do
	GARRADIN_USINE_USER=$(basename "$user")
	php $GARRADIN_CRON_SCRIPT
done
