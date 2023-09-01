<?php

namespace Paheko\Entities\Files;

use KD2\Graphics\Image;
use KD2\ZipReader;
use KD2\HTML\Markdown;

use Paheko\Static_Cache;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Web\Cache as Web_Cache;

use const Paheko\{DOCUMENT_THUMBNAIL_COMMANDS, WOPI_DISCOVERY_URL, CACHE_ROOT};

trait FileThumbnailTrait
{
	static protected array $_opendocument_extensions = ['odt', 'ods', 'odp', 'odg'];

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
		// Don't try to generate thumbnails for large files (> 25 MB)
		if ($this->size > 1024*1024*25) {
			return false;
		}

		if ($this->image) {
			return true;
		}

		$ext = $this->extension();

		if ($ext === 'md' || $ext === 'txt') {
			return true;
		}

		// We expect opendocument files to have an embedded thumbnail
		if (in_array($ext, self::$_opendocument_extensions)) {
			return true;
		}

		return $this->getDocumentThumbnailCommand() !== null;
	}

	protected function getDocumentThumbnailCommand(): ?string
	{
		if (!DOCUMENT_THUMBNAIL_COMMANDS || !is_array(DOCUMENT_THUMBNAIL_COMMANDS)) {
			return null;
		}

		static $libreoffice_extensions = ['doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'odt', 'ppt', 'pptx', 'odg'];
		static $mupdf_extensions = ['pdf', 'xps', 'cbz', 'epub', 'svg'];
		static $collabora_extensions = ['doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'odt', 'ppt', 'pptx', 'odg', 'pdf', 'svg'];

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

	/**
	 * Extract PNG thumbnail from odt/ods/odp/odg ZIP archives.
	 * This is the most efficient way to get a thumbnail.
	 */
	protected function extractOpenDocumentThumbnail(string $destination): bool
	{
		$zip = new ZipReader;

		// We are not going to extract the archive, so it does not matter
		$zip->enableSecurityCheck(false);

		$pointer = $this->getReadOnlyPointer();

		try {
			if ($pointer) {
				$zip->setPointer($pointer);
			}
			else {
				$zip->open($this->getLocalFilePath());
			}

			$i = 0;
			$found = false;

			foreach ($zip->iterate() as $path => $entry) {
				// There should not be more than 100 files in an opendocument archive, surely?
				if (++$i > 100) {
					break;
				}

				// We only care about the thumbnail
				if ($path !== 'Thumbnails/thumbnail.png') {
					continue;
				}

				// Thumbnail is larger than 500KB, abort, it's probably too weird
				if ($entry['size'] > 1024*500) {
					break;
				}

				$zip->extract($entry, $destination);
				$found = true;
				break;
			}
		}
		catch (\RuntimeException $e) {
			// Invalid archive
			$found = false;
		}

		unset($zip);

		if ($pointer) {
			fclose($pointer);
		}

		return $found;
	}

	/**
	 * Create a document thumbnail using external commands or Collabora Online API
	 */
	protected function createDocumentThumbnail(): ?string
	{
		$cache_id = sprintf(self::THUMB_CACHE_ID, $this->md5, 'document');
		$destination = Static_Cache::getPath($cache_id);

		if (in_array($this->extension(), self::$_opendocument_extensions) && $this->extractOpenDocumentThumbnail($destination)) {
			return $destination;
		}

		$command = $this->getDocumentThumbnailCommand();

		if (!$command) {
			return null;
		}

		if (file_exists($destination)) {
			return $destination;
		}

		$local_path = $this->getLocalFilePath();
		$tmpfile = null;

		if (!$local_path) {
			$tmpfile = tmpfile(CACHE_ROOT);
			$p = $this->getReadOnlyPointer();
			$fp = fopen($tmpfile, 'wb');

			while (!feof($p)) {
				fwrite($fp, fread($p, 8192));
			}

			fclose($p);
			fclose($fp);
			unset($p, $fp);
		}

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
					'file' => new \CURLFile($tmpfile ?? $local_path, $this->mime, $this->name),
				]);

				$fp = fopen($destination, 'wb');
				curl_setopt($curl, CURLOPT_FILE, $fp);

				curl_exec($curl);
				$info = curl_getinfo($curl);
				curl_close($curl);
				fclose($fp);

				if (($code = $info['http_code']) != 200) {
					Utils::safe_unlink($destination);
					throw new \RuntimeException('Cannot fetch thumbnail from Collabora: code ' . $code);
				}
			}
			else {
				if ($command === 'mupdf') {
					// The single '1' at the end is to tell only to render the first page
					$cmd = sprintf('mutool draw -F png -o %s -w 500 -h 500 -r 72 %s 1 2>&1',
						escapeshellarg($destination),
						escapeshellarg($tmpfile ?? $local_path)
					);
				}
				elseif ($command === 'unoconvert') {
					// --filter-options PixelWidth=500 --filter-options PixelHeight=500
					// see https://github.com/unoconv/unoserver/issues/85
					// see https://github.com/unoconv/unoserver/issues/86
					$cmd = sprintf('unoconvert --convert-to png %s %s 2>&1',
						escapeshellarg($tmpfile ?? $local_path),
						escapeshellarg($destination)
					);
				}

				$output = '';
				$code = Utils::exec($cmd, 5, null, function($data) use (&$output) { $output .= $data; });

				// Don't trust code as it can return != 0 even if generation was OK

				if (!file_exists($destination) || filesize($destination) < 10) {
					Utils::safe_unlink($destination);
					throw new \RuntimeException($command . ' execution failed with code: ' . $code . "\n" . $output);
				}
			}
		}
		finally {
			if ($tmpfile) {
				Utils::safe_unlink($tmpfile);
			}
		}

		return $destination;
	}

	/**
	 * Create a SVG thumbnail of a text/markdown file
	 * It's easy, we just transform it to HTML and embed the HTML in the SVG!
	 */
	protected function createSVGThumbnail(array $operations, string $destination): void
	{
		$width = 150;

		foreach ($operations as $operation) {
			if ($operation[0] === 'resize') {
				$width = (int) $operation[1];
				break;
			}
		}

		$text = substr($this->fetch(), 0, 1200);
		$text = Markdown::instance()->text($text);

		$out = '<svg version="1.1" viewBox="0 0 240 320" xmlns="http://www.w3.org/2000/svg" width="' . $width . '">
			<style>
			body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; padding: 1px; }
			table { border-collapse: collapse; width: 100% }
			table thead { background: #ddd }
			table td, table th { border: 2px solid #999; padding: .2em }
			ul, ol { padding-left: 1.5em }
			h1, h2, h3, h4, h5, h6, ul, ol, table, p { margin: .5em 0 }
			</style>
			<foreignObject x="0" y="0" width="1200" height="1200">
				<body xmlns="http://www.w3.org/1999/xhtml">' . $text . '</body>
			</foreignObject>
		</svg>';

		file_put_contents($destination, $out);
	}

	protected function createThumbnail(string $size, string $destination): void
	{
		$operations = self::ALLOWED_THUMB_SIZES[$size];

		if ($this->extension() === 'md' || $this->extension() === 'txt') {
			$this->createSVGThumbnail($operations, $destination);
			return;
		}

		$i = $this->asImageObject();

		// Always autorotate first
		$i->autoRotate();

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
				$this->createThumbnail($size, $destination);
			}
			catch (\RuntimeException $e) {
				throw new UserException('Impossible de créer la miniature', 500, $e);
			}
		}

		if ($this->extension() === 'md') {
			$type = 'image/svg+xml';
		}
		else {
			// We can lie here, it might be something else, it does not matter
			$type = 'image/webp';
		}

		header('Content-Type: ' . $type, true);
		$this->_serve($destination, false);

		if (in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_CONFIG])) {
			Web_Cache::link($this->uri(), $destination, $size);
		}
	}
}
