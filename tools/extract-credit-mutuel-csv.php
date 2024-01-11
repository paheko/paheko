<?php
/**
 * Extracteur de données des relevés de compte du Crédit Mutuel
 * à destination de Garradin (ou autre logiciel de compta)
 *
 * https://garradin.eu/
 *
 * Ce script prend en argument un répertoire contenant des extraits
 * de compte en PDF (ou un seul extrait de compte) et crée un fichier
 * CSV importable directement dans Garradin.
 *
 * Ce script requiert d'avoir installé Tabula (Java) :
 * https://github.com/tabulapdf/tabula-java
 *
 * Copyright (C) 2020 BohwaZ - licence AGPLv3
 */

// Ajuster cette constante en fonction du chemin où vous avez placé
// le JAR de Tabula
const TABULA_PATH = 'tabula.jar';

if (empty($argv[1]) || empty($argv[2])) {
	printf("Usage: %s REPERTOIRE_OU_FICHIER FICHIER_SORTIE_CSV\n", $argv[0]);
	exit(1);
}

if (!file_exists(TABULA_PATH)) {
	printf("Tabula introuvable: %s\n", TABULA_PATH);
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

if (is_file($path)) {
	$files = [$path];
}
else {
	$files = glob($path . '/*.pdf');
}

// Lecture du CSV
foreach ($files as $file) {
	printf("Lecture de %s…" . PHP_EOL, $file);

	$csv = shell_exec(sprintf('java -jar %s -g -l -p all %s', TABULA_PATH, escapeshellarg($file)));

	$csv = explode("\n", $csv);

	/*
		Date
		Date valeur
		Opération
		Débit EUROS
		Crédit EUROS
	 */
	foreach ($csv as $line) {
		$row = str_getcsv($line);

		if (count($row) < 2) {
			echo "Saut ligne vide\n";
			continue;
		}

		if (preg_match('!^Solde.*(?:AU\s+(\d+/\d+/\d+))!i', trim($row[0]), $match)) {
			if (null === $sum_header) {
				$row = [$match[1], null, $row[0], null, $row[2]];
				$out[$i++] = $row;
				$sum_header = $row;
				continue;
			}

			echo "Saut solde : ";
			echo implode(', ', $row);
			echo PHP_EOL;
			continue;
		}
		elseif (preg_match('!^(?:Solde|Total|Réf\s+:.*SOLDE)!i', trim($row[0]))) {
			echo "Saut solde : ";
			echo implode(', ', $row);
			echo PHP_EOL;
			continue;
		}

		if (null === $header) {
			echo "Saut entête\n";
			$header = $row;
			continue;
		}

		if ($header === $row) {
			echo "Saut répétition entête\n";
			continue;
		}

		if (count($row) !== count($header)) {
			echo "Ligne incohérente\n";
			var_dump($row); exit;
		}

		foreach ($row as &$cell) {
			$cell = preg_replace('/\s\s+/', ' ', $cell);
		}

		unset($cell);

		if (empty($row[0])) {
			$out[$i - 1][2] .= PHP_EOL . $row[2];
			continue;
		}

		$out[$i++] = $row;
	}
}

// Création du CSV de sortie
$fp = fopen($dest, 'w');

fputcsv($fp, ['Numéro d\'écriture', 'Date', 'Libellé', 'Compte de débit', 'Compte de crédit', 'Montant', 'Numéro pièce comptable', 'Référence paiement', 'Notes']);

foreach ($out as $line) {
	$label = $line[2];
	$notes = null;
	$ref = null;

	if (false !== ($pos = strpos($label, "\n"))) {
		$notes = trim(substr($label, $pos));
		$label = trim(substr($label, 0, $pos));
	}

	if (preg_match('/^VRST (REF.*)/', $label, $match)) {
		$label = 'Versement espèces';
		$ref = $match[1];
	}
	elseif (preg_match('/^REM CHQ (REF.*)/', $label, $match)) {
		$label = 'Remise de chèques';
		$ref = $match[1];
	}
	elseif (preg_match('/^FACTURE (SGT.*)/', $label, $match)) {
		$label = 'Frais bancaires Crédit Mutuel';
		$ref = $match[1];
	}
	elseif (preg_match('/^CHEQUE (\d+)/', $label, $match)) {
		$label = 'Chèque ' . $match[1];
		$ref = $match[1];
	}
	elseif (preg_match('/^VIR ROZO/', $label) && preg_match('/(REP\d+-\d+)/s', $notes, $match)) {
		$label = 'Virement Rozo';
		$ref = $match[1];
	}

	$amount = !empty($line[4]) ? $line[4] : '-' . $line[3];
	$amount = str_replace('.', '', $amount);

	fputcsv($fp, ['', $line[0], $label, '', '', $amount, '', $ref, $notes]);
}

fclose($fp);