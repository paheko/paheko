<?php
/**
 * Extracteur de données des relevés de compte Paypal
 * à destination de Garradin (ou autre logiciel de compta)
 *
 * https://garradin.eu/
 *
 * Ce script prend en argument un fichier CSV exporté de Paypal
 * https://business.paypal.com/merchantdata/reportHome?reportType=DLOG
 * et produit un import exploitable dans Garradin
 *
 * Copyright (C) 2020 BohwaZ - licence AGPLv3
 */

if (empty($argv[1]) || empty($argv[2])) {
	printf("Usage: %s FICHIER_PAYPAL_CSV FICHIER_SORTIE_CSV\n", $argv[0]);
	exit(1);
}

$path = $argv[1];
$dest = $argv[2];

if (!is_readable($path)) {
	die("Ne peut lire le répertoire\n");
}

$path = rtrim($path, '/');

$header = null;
$sum_header = null;
$out = [];
$i = 0;

if (!is_file($path)) {
	die("Le fichier Paypal est invalide\n");
}

// Lecture du CSV
printf("Lecture de %s…" . PHP_EOL, $path);

/*
	Date
	Heure
	Fuseau horaire
	Nom
	Type
	État
	Devise
	Avant commission
	Commission
	Net
	De l'adresse email
	À l'adresse email
	Numéro de transaction
	Adresse de livraison
	État de l'adresse
	Titre de l'objet
	Numéro de l'objet
	Montant des frais d'expédition et de traitement
	Montant de l'assurance
	TVA
	Nom de l'option 1
	Valeur de l'option 1
	Nom de l'option 2
	Valeur de l'option 2
	Numéro de la transaction de référence
	Numéro de facture
	Numéro de client
	Quantité
	Numéro de reçu
	Solde
	Adresse
	Adresse (suite)/District/Quartier
	Ville
	État/Province/Région/Comté/Territoire/Préfecture/République
	Code postal
	Pays
	Numéro de téléphone du contact
	Objet
	Remarque
	Indicatif pays
	Impact sur le solde
*/

$header = null;

$bom = "\xef\xbb\xbf";

// Read file from beginning.
$fp = fopen($path, 'r');

// Progress file pointer and get first 3 characters to compare to the BOM string.
if (fgets($fp, 4) !== $bom) {
    // BOM not found - rewind pointer to start of file.
    rewind($fp);
}

$l = 0;

static $required = ['Date', 'Nom', 'Type', 'Commission', 'Net', 'Objet', 'Remarque', 'Numéro de transaction'];

while (!feof($fp)) {
	$l++;
	$row = fgetcsv($fp);

	if (!$row) {
		break;
	}

	if (null === $header) {
		$header = $row;
		continue;
	}

	$c = (object) array_combine($header, $row);

	foreach ($required as $key) {
		if (!isset($c->$key)) {
			printf('Colonne "%s" manquante sur ligne %d' . PHP_EOL, $key, $l);
			continue(2);
		}
	}

	$out[] = $c;
}

fclose($fp);

// Création du CSV de sortie
$fp = fopen($dest, 'w');

fputcsv($fp, ['Numéro d\'écriture', 'Date', 'Libellé', 'Compte de débit', 'Compte de crédit', 'Montant', 'Numéro pièce comptable', 'Référence paiement', 'Notes']);

static $notes_keys = ['Nom', 'Objet', 'Remarque'];

foreach ($out as $c) {
	$label = $c->Type;

	if ($c->Nom) {
		$label = $c->Nom . ' - ' . $label;
	}

	$notes = '';
	$ref = $c->{'Numéro de transaction'};

	foreach ($notes_keys as $k) {
		if ($c->{$k}) {
			$notes .= $c->{$k} . "\n";
		}
	}

	$notes = trim($notes);

	if ($c->Commission != '0,00') {
		$amount = preg_replace('/\s+/U', '', $c->Commission);
		fputcsv($fp, ['', $c->Date, 'Commission PayPal sur transaction', '', '', $amount, '', $ref, $notes]);
	}

	$amount = preg_replace('/[\s ]+/U', '', $c->{'Avant commission'});
	fputcsv($fp, ['', $c->Date, $label, '', '', $amount, '', $ref, $notes]);
}

fclose($fp);
