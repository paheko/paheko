<?php

namespace Garradin\Entities\Files;

use KD2\Image;
use Garradin\DB;
use Garradin\Entity;
use Garradin\UserException;

class File extends Entity
{
	const TABLE = 'files';

	protected $id;
	protected $name;

	protected $_types = [
		'id'         => 'int',
		'label'      => 'name',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
	}

	public function delete(): bool
	{
		$return = parent::delete();

		// Clean up files
		Files::deleteOrphanFiles();

		return $return;
	}

	static protected function store(?string $path, string $name, string $source_path = null, $source_content = null): self
	{
		assert($path || $content);

		$finfo = \finfo_open(\FILEINFO_MIME_TYPE);
		$file = new self;
		$file->path = $path;

		if ($source_path && !$source_content)
		{
			$file->hash = sha1_file($source_path);
			$file->size = filesize($source_path);
			$file->type = finfo_file($finfo, $source_path);
		}
		else
		{
			$file->hash = sha1($source_content);
			$file->size = strlen($source_content);
			$file->type = finfo_buffer($finfo, $source_content);
		}

		$file->image = preg_match('/^image\/(?:png|jpe?g|gif)$/', $file->type);

		// Check that it's a real image
		if ($file->image) {
			try {
				if ($source_path && !$source_content) {
					$i = new Image($source_path);
				}
				else {
					$i = Image::createFromBlob($source_content);
				}

				// Recompress PNG files from base64, assuming they are coming
				// from JS canvas which doesn't know how to gzip (d'oh!)
				if ($i->format() == 'png' && null !== $source_content) {
					$source_content = $i->output('png', true);
					$file->hash = sha1($source_content);
					$file->size = strlen($source_content);
				}

				unset($i);
			}
			catch (\RuntimeException $e) {
				if (strstr($e->getMessage(), 'No suitable image library found')) {
					throw new \RuntimeException('Le serveur n\'a aucune bibliothèque de gestion d\'image installée, et ne peut donc pas accepter les images. Installez Imagick ou GD.');
				}

				throw new UserException('Fichier image invalide');
			}
		}

		$db = DB::getInstance();

		$db->begin();

		// Il peut arriver que l'on renvoie ici un fichier déjà stocké, auquel cas, ne pas le re-stocker
		if ($content_id = $db->firstColumn('SELECT id FROM files_contents WHERE hash = ?;', $hash)) {
			$file->content_id = $content_id;
		}
		else {
			$db->preparedQuery('INSERT INTO files_contents (hash, size) VALUES (?, ?);', [$file->hash, (int)$file->size]);
			$file->content_id = $db->lastInsertRowID();

			if (!Files::callStorage('store', $file, $path, $content)) {
				throw new UserException('Le fichier n\'a pas pu être enregistré.');
			}
		}

		$file->save();

		$db->commit();

		return $file;
	}

	/**
	 * Upload de fichier à partir d'une chaîne en base64
	 * @param  string $name
	 * @param  string $content
	 * @return File
	 */
	static public function storeFromBase64(?string $path, string $name, string $encoded_content): self
	{
		$content = base64_decode($encoded_content);
		return self::store($path, $name, null, $content);
	}

	/**
	 * Upload du fichier par POST
	 * @param  array  $file  Caractéristiques du fichier envoyé
	 * @return File
	 */
	static public function upload(?string $path, array $file): self
	{
		if (!empty($file['error']))
		{
			throw new UserException(self::getErrorMessage($file['error']));
		}

		if (empty($file['size']) || empty($file['name']))
		{
			throw new UserException('Fichier reçu invalide : vide ou sans nom de fichier.');
		}

		if (!is_uploaded_file($file['tmp_name']))
		{
			throw new \RuntimeException('Le fichier n\'a pas été envoyé de manière conventionnelle.');
		}

		$name = preg_replace('/\s+/', '_', $file['name']);
		$name = preg_replace('/[^\d\w._-]/ui', '', $name);

		return self::store($path, $name, $file['tmp_name']);
	}

}
