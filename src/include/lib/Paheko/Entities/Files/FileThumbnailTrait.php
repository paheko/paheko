<?php

namespace Paheko\Entities\Files;

use KD2\Graphics\Image;
use KD2\HTML\Markdown;
use KD2\ErrorManager;

use Paheko\Files\Conversion;
use Paheko\Static_Cache;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Web\Cache as Web_Cache;

use const Paheko\{WWW_URL, BASE_URL, ENABLE_FILE_THUMBNAILS};

trait FileThumbnailTrait
{
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
			$pointer = null;

			if (!$path) {
				return null;
			}
		}
		else {
			$path = $this->getLocalFilePath();
			$pointer = $path !== null ? null : $this->getReadOnlyPointer();
		}

		try {
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
		catch (\Exception $e) {
			if (strstr($e->getMessage(), 'Invalid image format')) {
				return null;
			}

			throw $e;
		}
	}

	public function thumb_uri($size = null, bool $with_hash = true): ?string
	{
		$ext = $this->getThumbnailExtension();

		if (null === $ext) {
			return null;
		}

		if (is_int($size)) {
			$size .= 'px';
		}

		$size = isset(self::ALLOWED_THUMB_SIZES[$size]) ? $size : key(self::ALLOWED_THUMB_SIZES);
		$uri = sprintf('%s.%s.%s', $this->uri(), $size, $ext);

		if ($with_hash) {
			$uri .= '?h=' . $this->getShortEtag();
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

	public function getThumbnailExtension(): ?string
	{
		// Don't try to generate thumbnails for large files (> 25 MB), except if it's a video
		if ($this->size > 1024*1024*25 && substr($this->mime ?? '', 0, 6) !== 'video/') {
			return null;
		}

		if ($this->image) {
			return 'webp';
		}

		$ext = $this->extension();

		if (!$ext) {
			return null;
		}

		if ($ext === 'md' || $ext === 'txt') {
			return 'svg';
		}
		// We expect opendocument files to have an embedded thumbnail
		elseif (ENABLE_FILE_THUMBNAILS && Conversion::canExtractThumbnail($ext)) {
			return 'webp';
		}
		elseif (ENABLE_FILE_THUMBNAILS && Conversion::canConvert($ext, 'png')) {
			return 'webp';
		}

		return null;
	}

	public function hasThumbnail(): bool
	{
		return $this->getThumbnailExtension() !== null;
	}

	/**
	 * Create a document thumbnail using external commands or Collabora Online API
	 */
	protected function createDocumentThumbnail(): ?string
	{
		$cache_id = sprintf(self::THUMB_CACHE_ID, $this->md5, 'document');
		$destination = Static_Cache::getPath($cache_id);
		$ext = $this->extension();

		// Try to extract integrated thumbnail first, for OpenDocument files, if it's available
		if (Conversion::canExtractThumbnail($ext)
			&& Conversion::extractFileThumbnail($this, $destination)) {
			return $destination;
		}

		if (!Conversion::canConvert($ext, 'png')) {
			return null;
		}

		$source = $this->getLocalOrCacheFilePath();
		$r = null;

		if ($source) {
			$r = Conversion::convert($source, $destination, 'png', $this->size, $this->mime);
		}

		if (!$r) {
			Utils::safe_unlink($destination);
			return null;
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
	public function serveThumbnail(?string $size = null): void
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
