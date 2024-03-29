<?php

namespace Paheko\Entities\Files;

use KD2\Graphics\Image;
use KD2\ZipReader;
use KD2\HTML\Markdown;
use KD2\ErrorManager;

use Paheko\Static_Cache;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Web\Cache as Web_Cache;

use const Paheko\{DOCUMENT_THUMBNAIL_COMMANDS, WOPI_DISCOVERY_URL, CACHE_ROOT, WWW_URL, BASE_URL, ADMIN_URL};

trait FileThumbnailTrait
{
	static protected array $_opendocument_extensions = ['odt', 'ods', 'odp', 'odg'];

	protected function deleteThumbnails(): void
	{
		if (!$this->image && !$this->hasThumbnail()) {
			return;
		}

		if (!isset($this->md5)) {
			return;
		}

		// clean up thumbnails
		foreach (self::ALLOWED_THUMB_SIZES as $size => $operations) {
			Static_Cache::remove(sprintf(self::THUMB_CACHE_ID, $this->md5, $size));

			$uri = $this->thumb_uri($size, false);

			if ($uri) {
				Web_Cache::delete($uri);
			}
		}

		if (!$this->image && $this->hasThumbnail()) {
			Static_Cache::remove(sprintf(self::THUMB_CACHE_ID, $this->md5, 'document'));
		}
	}

	public function asImageObject(): ?Image
	{
		if (!$this->image) {
			$path = $this->createDocumentThumbnail();

			if (!$path) {
				return null;
			}
		}
		else {
			$path = $this->getLocalFilePath();
			$pointer = $path !== null ? null : $this->getReadOnlyPointer();
		}

		if ($path) {
			$i = new Image($path);
		}
		elseif ($pointer) {
			$i = Image::createFromPointer($pointer, null, true);
		}
		else {
			return null;
		}

		return $i;
	}

	public function thumb_uri($size = null, bool $with_hash = true): ?string
	{
		// Don't try to generate thumbnails for large files (> 25 MB), except if it's a video
		if ($this->size > 1024*1024*25 && substr($this->mime ?? '', 0, 6) !== 'video/') {
			return null;
		}

		$ext = $this->extension();

		if ($this->image) {
			$ext = 'webp';
		}
		elseif ($ext === 'md' || $ext === 'txt') {
			$ext = 'svg';
		}
		// We expect opendocument files to have an embedded thumbnail
		elseif (in_array($ext, self::$_opendocument_extensions)) {
			$ext = 'webp';
		}
		elseif (null !== $this->getDocumentThumbnailCommand()) {
			$ext = 'webp';
		}
		else {
			return null;
		}

		if (is_int($size)) {
			$size .= 'px';
		}

		$size = isset(self::ALLOWED_THUMB_SIZES[$size]) ? $size : key(self::ALLOWED_THUMB_SIZES);
		$uri = sprintf('%s.%s.%s', $this->uri(), $size, $ext);

		if ($with_hash) {
			$uri .= '?h=' . substr($this->etag(), 0, 10);
		}

		return $uri;
	}

	public function thumb_url($size = null, bool $with_hash = true): ?string
	{
		$uri = $this->thumb_uri($size, $with_hash);

		if (!$uri) {
			return $uri;
		}

		$base = in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_MODULES, self::CONTEXT_CONFIG]) ? WWW_URL : BASE_URL;
		return $base . $uri;
	}

	public function hasThumbnail(): bool
	{
		return $this->thumb_url() !== null;
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
		// Generate video thumbnails, for up to 1GB in size
		elseif (in_array('ffmpeg', DOCUMENT_THUMBNAIL_COMMANDS)
			&& $this->mime
			&& substr($this->mime, 0, 6) === 'video/'
			&& $this->size < 1024*1024*1024) {
			return 'ffmpeg';
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

		if (file_exists($destination) && filesize($destination)) {
			return $destination;
		}

		// Don't overload servers with large documents, this is useless
		if (($command === 'collabora' || $command === 'unoconvert') && $this->size >= 15*1024*1024) {
			return null;
		}
		elseif ($command === 'mupdf' && $this->size >= 50*1024*1024) {
			return null;
		}

		$local_path = $this->getLocalFilePath();
		$tmpfile = null;

		if (!$local_path) {
			$p = $this->getReadOnlyPointer();

			if (!$p) {
				// File does not exist in storage backend, we can't generate a thumbnail
				return null;
			}

			$tmpfile = tempnam(CACHE_ROOT, 'thumb-');
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
				$url = sprintf('%s://%s:%s/lool/convert-to', $url['scheme'], $url['host'], $url['port'] ?? ($url['scheme'] === 'https' ? 443 : 80));

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
					'file' => new \CURLFile($tmpfile ?? $local_path, $this->mime, md5($this->name) . '.' . $this->extension()),
				]);

				$fp = fopen($destination, 'wb');
				curl_setopt($curl, CURLOPT_FILE, $fp);

				curl_exec($curl);
				$info = curl_getinfo($curl);

				if ($error = curl_error($curl)) {
					Utils::safe_unlink($destination);
					throw new \RuntimeException(sprintf('cURL error on "%s": %s', $url, $error));
				}

				curl_close($curl);
				fclose($fp);
				unset($curl);

				if (($code = $info['http_code']) != 200 || @filesize($destination) < 10) {
					Utils::safe_unlink($destination);
					throw new \RuntimeException('Cannot fetch thumbnail from Collabora: code ' . $code . "\n" . json_encode($info));
				}
			}
			else {
				if ($command === 'mupdf') {
					// The single '1' at the end is to tell only to render the first page
					$cmd = sprintf('mutool draw -i -N -q -F png -o %s -w 500 -h 500 -r 72 %s 1 2>&1',
						Utils::escapeshellarg($destination),
						Utils::escapeshellarg($tmpfile ?? $local_path)
					);
				}
				elseif ($command === 'unoconvert') {
					// --filter-options PixelWidth=500 --filter-options PixelHeight=500
					// see https://github.com/unoconv/unoserver/issues/85
					// see https://github.com/unoconv/unoserver/issues/86
					$cmd = sprintf('unoconvert --convert-to png %s %s 2>&1',
						Utils::escapeshellarg($tmpfile ?? $local_path),
						Utils::escapeshellarg($destination)
					);
				}
				elseif ($command === 'ffmpeg') {
					$cmd = sprintf('ffmpeg -ss 00:00:02 -i %s -frames:v 1 -f image2 -c png -vf scale="min(900\, iw)":-1 %s 2>&1',
						Utils::escapeshellarg($tmpfile ?? $local_path),
						Utils::escapeshellarg($destination)
					);
				}

				$output = '';
				$code = null;
				$output = Utils::quick_exec($cmd, 5, $code);

				// Don't trust code as it can return != 0 even if generation was OK
				if (!file_exists($destination) || filesize($destination) < 10) {
					Utils::safe_unlink($destination);
					$e = new \RuntimeException($command . ' execution failed with code: ' . $code . "\n" . $output);

					if ($command === 'mupdf' && $code === 0) {
						// MuPDF can fail for a number of reasons: password protection, broken document, etc.
						// Usually when error is zero it means that calling mupdf works, but not the conversion
						// so just ignore it, no need to report it, just log it
						ErrorManager::logException($e);
						return null;
					}

					throw $e;
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

	protected function createThumbnail(string $size, string $destination): bool
	{
		$operations = self::ALLOWED_THUMB_SIZES[$size];

		if ($this->extension() === 'md' || $this->extension() === 'txt') {
			$this->createSVGThumbnail($operations, $destination);
			return true;
		}

		$i = $this->asImageObject();

		if (!$i) {
			return false;
		}

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
		return true;
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
				$success = $this->createThumbnail($size, $destination);
			}
			catch (\RuntimeException $e) {
				ErrorManager::reportExceptionSilent($e);
				$success = false;
			}

			if (!$success) {
				header('Content-Type: image/svg+xml; charset=utf-8', true);
				echo $this->getMissingThumbnail($size);
				return;
			}
		}

		$ext = $this->extension();

		if ($ext === 'md' || $ext === 'txt') {
			$type = 'image/svg+xml';
		}
		else {
			// We can lie here, it might be something else, it does not matter
			$type = 'image/webp';
		}

		header('Content-Type: ' . $type, true);
		$this->_serve($destination, false);

		if (in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_CONFIG])) {
			$uri = $this->thumb_uri($size, false);
			Web_Cache::link($uri, $destination);
		}
	}

	protected function getMissingThumbnail(string $size): string
	{
		$w = preg_replace('/[^\d]+/', '', $size) ?: 150;
		$h = intval($w / (2/3));

		return sprintf('<svg width="%d" height="%d" version="1.1" viewBox="0 0 396.9 396.9" xmlns="http://www.w3.org/2000/svg">
				<g transform="translate(-28.52 -6.592)" fill="#ccc" stroke="none">
					<path d="m264.7 148.4v-69.85q3.44 2.117 5.556 4.233l60.06 60.32q2.117 1.852 4.233 5.292zm-18.79 4.762q0 5.821 4.233 9.79 4.233 3.969 10.05 4.233h80.17v155.8q0 6.085-3.969 10.05t-10.05 4.233h-198.4q-6.085 0-10.05-4.233-3.969-4.233-4.233-10.05v-236q0-6.085 4.233-10.05 4.233-3.969 10.05-4.233h118z" />
				</g>
			</svg>',
			$w, $h
		);
	}
}
