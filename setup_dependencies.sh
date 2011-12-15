#!/bin/sh

# Mise en place/à jour des dépendances

# Template Lite
svn export https://svn.kd2.org/svn/misc/libs/template_lite/ include/template_lite/

# Liste des pays
svn export https://svn.kd2.org/svn/misc/libs/i18n/countries/countries_fr.php include/countries_fr.php

# Passphrase
svn export https://svn.kd2.org/svn/misc/libs/i18n/passphrase/lib.passphrase.french.php include/
