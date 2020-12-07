<?php

namespace Garradin\Files\Storage;

use const Garradin\FILE_STORAGE_CONFIG;

/**
 * This class provides storage, same as FileSystem,
 * but adds the ability to define a custom quota.
 * To that end, just append ;quota=XXX to FILE_STORAGE_CONFIG
 * where XXX is the maximum storage allowed for that user, in bytes
 */
class FileSystemQuota extends FileSystem
{
	static protected $quota;
	static protected $root;

	static protected function _getRoot()
	{
		if (null === self::$root) {
			if (!FILE_STORAGE_CONFIG) {
				throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide).');
			}

			$target = strtok(FILE_STORAGE_CONFIG, ';');

			if (!is_writable($target)) {
				throw new \RuntimeException('Le répertoire de stockage des fichiers est protégé contre l\'écriture.');
			}

			strtok('=');
			$size = (int) strtok('');

			if (!$size) {
				throw new \RuntimeException('Aucun quota indiqué dans FILE_STORAGE_CONFIG');
			}

			$target = rtrim($target, DIRECTORY_SEPARATOR);

			self::$root = realpath($target);
			self::$quota = $size;
		}

		return self::$root;
	}

	static public function getRemainingQuota(): int
	{
		return self::getTotalSize() - self::getQuota();
	}

	static public function getQuota(): int
	{
		self::_getRoot(); // Make sure quota is loaded
		return self::$quota;
	}
}
