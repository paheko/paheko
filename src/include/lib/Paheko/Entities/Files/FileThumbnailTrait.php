<?php

namespace Paheko\Entities\Files;

use KD2\Graphics\Image;

use Paheko\Static_Cache;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Web\Cache as Web_Cache;

use const Paheko\{DOCUMENT_THUMBNAIL_COMMANDS, WOPI_DISCOVERY_URL, CACHE_ROOT};

trait FileThumbnailTrait
{
	protected function deleteThumbnails(): void
	{
		// clean up thumbnails
		foreach (self::ALLOWED_THUMB_SIZES as $key => $operations)
		{
			Static_Cache::remove(sprintf(self::THUMB_CACHE_ID, $this->md5, $key));
		}

		if (!$this->image && $this->hasThumbnail()) {
			Static_Cache::remove(sprintf(self::THUMB_CACHE_ID, $this->md5, 'document'));
		}
	}

	public function asImageObject(): Image
	{
		if (!$this->image) {
			$path = $this->createDocumentThumbnail();

			if (!$path) {
				throw new \RuntimeException('Cannot get image object as document thumbnail does not exist');
			}
		}
		else {
			$path = $this->getLocalFilePath();
			$pointer = $path === null ? null : $this->getReadOnlyPointer();
		}

		if ($path) {
			$i = new Image($path);
		}
		else {
			$i = Image::createFromPointer($pointer, null, true);
		}

		return $i;
	}

	public function thumb_url($size = null): string
	{
		if (!$this->hasThumbnail()) {
			return $this->url();
		}

		if (is_int($size)) {
			$size .= 'px';
		}

		$size = isset(self::ALLOWED_THUMB_SIZES[$size]) ? $size : key(self::ALLOWED_THUMB_SIZES);
		return sprintf('%s?%dpx', $this->url(), $size);
	}

	public function hasThumbnail(): bool
	{
		if ($this->image) {
			return true;
		}

		return $this->getDocumentThumbnailCommand() !== null;
	}

	protected function getDocumentThumbnailCommand(): ?string
	{
		if (!DOCUMENT_THUMBNAIL_COMMANDS || !is_array(DOCUMENT_THUMBNAIL_COMMANDS)) {
			return null;
		}

		static $libreoffice_extensions = ['doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'odt', 'ppt', 'pptx'];
		static $mupdf_extensions = ['pdf', 'xps', 'cbz', 'epub', 'svg'];
		static $collabora_extensions = ['doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'odt', 'ppt', 'pptx', 'pdf', 'svg'];

		$ext = $this->extension();

		if (in_array('mupdf', DOCUMENT_THUMBNAIL_COMMANDS) && in_array($ext, $mupdf_extensions)) {
			return 'mupdf';
		}
		elseif (in_array('unoconvert', DOCUMENT_THUMBNAIL_COMMANDS) && in_array($ext, $libreoffice_extensions)) {
			return 'unoconvert';
		}
		elseif (in_array('collabora', DOCUMENT_THUMBNAIL_COMMANDS)
			&& class_exists('CurlFile')
			&& in_array($ext, $collabora_extensions)
			&& $this->getWopiURL()) {
			return 'collabora';
		}

		return null;
	}

	protected function createDocumentThumbnail(): ?string
	{
		$command = $this->getDocumentThumbnailCommand();

		if (!$command) {
			return null;
		}

		$cache_id = sprintf(self::THUMB_CACHE_ID, $this->md5, 'document');
		$destination = Static_Cache::getPath($cache_id);

		if (Static_Cache::exists($cache_id)) {
			return $destination;
		}

		$local_path = $this->getLocalFilePath();
		$path = $local_path;

		if (!$local_path) {
			$path = tmpfile(CACHE_ROOT);
			$p = $this->getReadOnlyPointer();
			$fp = fopen($path, 'wb');

			while (!feof($p)) {
				fwrite($fp, fread($p, 8192));
			}

			fclose($p);
			fclose($fp);
			unset($p, $fp);
		}

		$tmpdir = null;

		try {
			if ($command === 'collabora') {
				$url = parse_url(WOPI_DISCOVERY_URL);
				$url = sprintf('%s://%s:%s/lool/convert-to', $url['scheme'], $url['host'], $url['port']);

				// see https://vmiklos.hu/blog/pdf-convert-to.html
				// but does not seem to be working right now (limited to PDF export?)
				/*
				$options = [
					'PageRange' => ['type' => 'string', 'value' => '1'],
					'PixelWidth' => ['type' => 'int', 'value' => 10],
					'PixelHeight' => ['type' => 'int', 'value' => 10],
				];
				*/

				$curl = \curl_init($url);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, [
					'format' => 'png',
					//'options' => json_encode($options),
					'file' => new \CURLFile($path, $this->mime, $this->name),
				]);

				$fp = fopen($destination, 'wb');
				curl_setopt($curl, CURLOPT_FILE, $fp);

				curl_exec($curl);
				$info = curl_getinfo($curl);
				curl_close($curl);
				fclose($fp);

				if (($code = $info['http_code']) != 200) {
					throw new \RuntimeException('Cannot fetch thumbnail from Collabora: code ' . $code);
				}
			}
			else {
				if ($command === 'mupdf') {
					// The single '1' at the end is to tell only to render the first page
					$cmd = sprintf('mutool draw -F png -o %s -w 500 -h 500 -r 72 %s 1 2>&1', escapeshellarg($destination), escapeshellarg($path));
				}
				elseif ($command === 'unoconvert') {
					// --filter-options PixelWidth=500 --filter-options PixelHeight=500
					// see https://github.com/unoconv/unoserver/issues/85
					// see https://github.com/unoconv/unoserver/issues/86
					$cmd = sprintf('unoconvert --convert-to png %s %s 2>&1', escapeshellarg($path), escapeshellarg($destination));
				}

				$output = '';
				$code = Utils::exec($cmd, 5, null, function($data) use (&$output) { $output .= $data; });

				// Don't trust code as it can return != 0 even if generation was OK

				if (!file_exists($destination) || filesize($destination) < 10) {
					throw new \RuntimeException($command . ' execution failed with code: ' . $code . "\n" . $output);
				}
			}
		}
		finally {
			if (!$local_path) {
				Utils::safe_unlink($path);
			}

			if ($tmpdir) {
				Utils::deleteRecursive($tmpdir, true);
			}
		}

		return $destination;
	}

	/**
	 * Envoie une miniature à la taille indiquée au client HTTP
	 */
	public function serveThumbnail(string $size = null): void
	{
		if (!$this->hasThumbnail()) {
			throw new UserException('Il n\'est pas possible de fournir une miniature pour ce fichier.', 404);
		}

		if (!array_key_exists($size, self::ALLOWED_THUMB_SIZES)) {
			throw new UserException('Cette taille de miniature n\'est pas autorisée.');
		}

		$cache_id = sprintf(self::THUMB_CACHE_ID, $this->md5, $size);
		$destination = Static_Cache::getPath($cache_id);

		if (!Static_Cache::exists($cache_id)) {
			try {
				$i = $this->asImageObject();

				// Always autorotate first
				$i->autoRotate();

				$operations = self::ALLOWED_THUMB_SIZES[$size];
				$allowed_operations = ['resize', 'cropResize', 'flip', 'rotate', 'crop', 'trim'];

				if (!$this->image) {
					array_unshift($operations, ['trim']);
				}

				foreach ($operations as $operation) {
					$arguments = array_slice($operation, 1);
					$operation = $operation[0];

					if (!in_array($operation, $allowed_operations)) {
						throw new \InvalidArgumentException('Opération invalide: ' . $operation);
					}

					$i->$operation(...$arguments);
				}

				$format = null;

				if ($i->format() !== 'gif') {
					$format = ['webp', null];
				}

				$i->save($destination, $format);
			}
			catch (\RuntimeException $e) {
				throw new UserException('Impossible de créer la miniature', 500, $e);
			}
		}

		// We can lie here, it might be something else, it does not matter
		header('Content-Type: image/png', true);
		$this->_serve($destination, false);

		if (in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_CONFIG])) {
			Web_Cache::link($this->uri(), $destination, $size);
		}
	}
}
