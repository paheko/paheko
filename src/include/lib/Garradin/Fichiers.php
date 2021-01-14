<?php

namespace Garradin;

use KD2\Graphics\Image;
use Garradin\Membres\Session;

class Fichiers
{

	/**
	 * Vérifie si le hash fourni n'est pas déjà stocké
	 * Utile pour par exemple reconnaître un ficher dont le contenu est déjà stocké, et éviter un nouvel upload
	 * @param  string $hash Hash SHA1
	 * @return boolean      TRUE si le hash est déjà présent dans fichiers_contenu, FALSE sinon
	 */
	static public function checkHash($hash)
	{
		return (boolean) DB::getInstance()->firstColumn(
			'SELECT 1 FROM fichiers_contenu WHERE hash = ?;', 
			trim(strtolower($hash))
		);
	}


	/**
	 * Enlève d'une liste de fichiers ceux qui sont mentionnés dans un texte wiki
	 * @param  array $files Liste de fichiers
	 * @param  string $text  texte wiki
	 * @return array        Un tableau qui ne contient pas les fichiers mentionnés dans $text
	 */
	static public function filterFilesUsedInText($files, $text)
	{
		$used = self::listFilesUsedInText($text);

		return array_filter($files, function ($row) use ($used) {
			return !in_array($row->id, $used);
		});
	}

}