<?php

namespace Paheko\Files;

use Paheko\Entities\Files\File;
use Paheko\Static_Cache;
use Paheko\Utils;
use Paheko\UserException;

use const Paheko\{WOPI_DISCOVERY_URL, CONVERSION_TOOLS, CACHE_ROOT, LOCAL_SECRET_KEY, ADMIN_URL};

use KD2\ErrorManager;
use KD2\HTTP;
use KD2\HTTP\Server;
use KD2\Office\ToText;
use KD2\ZipReader;

class Conversion
{
	const TYPES = [
		'odt'      => 'application/vnd.oasis.opendocument.text',
		'doc'      => 'application/msword',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'ods'      => 'application/vnd.oasis.opendocument.spreadsheet',
		'xls'      => 'application/vnd.ms-excel',
		'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'odp'      => 'application/vnd.oasis.opendocument.presentation',
		'ppt'      => 'application/vnd.ms-powerpoint',
		'pps'      => 'application/vnd.ms-powerpoint',
		'pptx'     => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'ppsx'     => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'odg'      => 'application/vnd.oasis.opendocument.graphics',
		'rtf'      => 'application/rtf',
		'gnumeric' => 'application/x-gnumeric',
		'csv'      => 'text/csv',
		'pdf'      => 'application/pdf',
		'svg'      => 'image/svg+xml',
		'svgz'     => 'image/svg+xml',
		'cbz'      => 'application/vnd.comicbook+zip',
		'epub'     => 'application/epub+zip',
		'xps'      => 'application/vnd.ms-xpsdocument',
		'fb2'      => 'text/fb2+xml',
		'mobi'     => 'application/x-mobipocket-ebook',
	];

	/**
	 * List of XML-based office documents that we can convert using KD2\Office\ToText
	 */
	const XML_FORMATS = ['ods', 'odt', 'odp', 'pptx', 'xlsx', 'docx'];

	/**
	 * @see https://www.collaboraonline.com/document-conversion/
	 */
	const LIBREOFFICE_FORMATS = ['odt', 'doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'ppt', 'pptx', 'odg', 'rtf', 'gnumeric', 'csv', 'svg', 'pdf'];

	/**
	 * PDF is not included on purpose as it is slow / crashes
	 * @see https://api1.onlyoffice.com/editors/conversionapi
	 */
	const ONLYOFFICE_FORMATS = ['doc', 'docx', 'ods', 'xls', 'xlsx', 'odp', 'odt', 'ppt', 'pptx', 'odg', 'rtf', 'csv'];

	/**
	 * @see https://mupdf.readthedocs.io/en/latest/quick-start-guide.html#supported-file-formats
	 */
	const MUPDF_FORMATS = ['pdf', 'xps', 'cbz', 'epub', 'svg', 'svgz', 'mobi', 'fb2'];

	/**
	 * @see https://help.gnome.org/users/gnumeric/stable/sect-file-formats.html.en
	 */
	const GNUMERIC_FORMATS = ['gnumeric', 'xlsx', 'xls', 'ods'];

	/**
	 * Files that may contain a thumbnail
	 */
	const OPENDOCUMENT_FORMATS = ['odt', 'ods', 'odp', 'odg'];

	const FFMPEG_EXTENSIONS = ['mp4', 'mkv', 'avi', 'webm', 'flv', 'vob', 'ogv', 'wmv', 'm4v', 'mpg', 'mpeg', 'mp2', '3gp', '3g2'];

	static public function getToolsList(): array
	{
		static $tools = null;

		if (null === $tools && CONVERSION_TOOLS !== null) {
			$tools = array_merge(array_values(CONVERSION_TOOLS), array_keys(CONVERSION_TOOLS));
			$tools = array_filter($tools, 'is_string');
		}

		return $tools ?? [];
	}

	static public function canConvert(string $extension, string $to): bool
	{
		$tools = self::getToolsList();

		if (in_array('mupdf', $tools, true)
			&& ($to === 'png' || $to === 'txt')
			&& in_array($extension, self::MUPDF_FORMATS, true)) {
			return true;
		}

		if (in_array('ffmpeg', $tools, true)
			&& $to === 'png'
			&& in_array($extension, self::FFMPEG_EXTENSIONS, true)) {
			return true;
		}

		if ((in_array('collabora', $tools, true) || in_array('unoconv', $tools, true) || in_array('unoconvert', $tools, true))
			&& ($to === 'csv' || $to === 'pdf' || $to === 'png' || $to === 'txt')
			&& in_array($extension, self::LIBREOFFICE_FORMATS, true)) {
			return true;
		}

		if (in_array('onlyoffice', $tools, true)
			&& ($to === 'csv' || $to === 'pdf' || $to === 'png' || $to === 'txt')
			&& in_array($extension, self::ONLYOFFICE_FORMATS, true)) {
			return true;
		}

		if (in_array('ssconvert', $tools, true)
			&& ($to === 'csv' || $to === 'pdf' || $to === 'png' || $to === 'txt')
			&& in_array($extension, self::GNUMERIC_FORMATS, true)) {
			return true;
		}

		return false;
	}

	static public function canConvertToCSV(): bool
	{
		return self::canConvert('ods', 'csv') && self::canConvert('xlsx', 'csv');
	}

	static public function canConvertToText(string $extension): bool
	{
		if (in_array($extension, self::XML_FORMATS, true)) {
			return true;
		}

		return self::canConvert($extension, 'txt');
	}

	static public function canExtractThumbnail(string $extension): bool
	{
		return in_array($extension, self::OPENDOCUMENT_FORMATS, true);
	}

	static public function fileToText(File $file, ?string $content): ?string
	{
		$extension = $file->extension();

		// Use KD2\Office\ToText when possible (faster)
		if (in_array($extension, self::XML_FORMATS, true)) {
			// Limit file size to 25MB
			if ($file->size >= 25*1024*1024) {
				return null;
			}

			$source = compact('content');

			if (null === $source['content']) {
				// Prefer pointer
				$source['pointer'] = $file->getReadOnlyPointer();
				$source['path'] = $source['pointer'] ? null : $file->getLocalFilePath();
			}

			return ToText::from($source);
		}

		// Limit file size to 250MB
		if ($file->size >= 250*1024*1024) {
			return null;
		}

		// Unused variable
		unset($content);

		$path = $file->getLocalFilePath();
		$is_cache = false;
		$tmp_id = 'to-text-' . $file->hash_id;
		$tmp_dest = Static_Cache::create($tmp_id, new \DateTime('+1 hour'));

		if (!$path) {
			$path = $file->getLocalOrCacheFilePath();
			$is_cache = true;
		}

		try {
			if (!self::convert($path, $tmp_dest, 'txt', $file->size, $file->mime)) {
				return null;
			}

			return Static_Cache::getAndRemove($tmp_id);
		}
		catch (\OverflowException $e) {
			// PDF document was probably too large, execution failed
			ErrorManager::logException($e);
			return null;
		}
		finally {
			if ($is_cache) {
				$file->deleteLocalFileCache();
			}
		}
	}

	/**
	 * Extract PNG thumbnail from odt/ods/odp/odg ZIP archives.
	 * This is the most efficient way to get a thumbnail.
	 */
	static public function extractFileThumbnail(File $file, string $destination): bool
	{
		if (!self::canExtractThumbnail($file->extension())) {
			throw new \LogicException('Cannot extract thumbnail from file: ' . $file->name);
		}

		$zip = new ZipReader;

		$pointer = $file->getReadOnlyPointer();

		try {
			if ($pointer) {
				$zip->setPointer($pointer);
			}
			else {
				$zip->open($file->getLocalFilePath());
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
		catch (\InvalidArgumentException|\RuntimeException|\OutOfBoundsException $e) {
			// Invalid archive
			$found = false;
		}

		unset($zip);

		if ($pointer) {
			fclose($pointer);
		}

		return $found;
	}

	static public function convert(string $source, string $destination, string $format, int $size, string $mime): bool
	{
		$tools = self::getToolsList();

		// Generate video thumbnails
		// We don't need the file extension here, as long as it's any kind of video
		if (in_array('ffmpeg', $tools, true)
			&& substr($mime, 0, 6) === 'video/') {
			// Dismiss for files larger than 1GB in size
			if ($size > 1024*1024*1024) {
				return false;
			}

			if ($format !== 'png') {
				throw new \InvalidArgumentException('Invalid conversion format for ffmpeg: ' . $format);
			}

			return self::ffmpeg($source, $destination);
		}

		$extension = array_search($mime, self::TYPES, true);

		// Unknown MIME type
		if (!$extension) {
			return false;
		}

		if (in_array('mupdf', $tools, true)
			&& in_array($extension, self::MUPDF_FORMATS, true)) {
			// Don't generate thumbnails for large documents
			if ($format === 'png'
				&& $size > 50*1024*1024) {
				return false;
			}

			return self::mupdf($source, $destination, $format, $extension);
		}

		// Files larger than 15MB shouldn't get a thumbnail
		if ($format === 'png'
			&& $size > 15*1024*1024) {
			return false;
		}

		// Consider Collabora to handle the same formats as Libreoffice
		if (in_array('collabora', $tools, true)
			&& in_array($extension, self::LIBREOFFICE_FORMATS, true)) {
			return self::collabora($source, $destination, $format, $mime);
		}

		$cmd = null;

		// Use ssconvert from Gnumeric to convert to CSV
		if ($format === 'csv'
			&& in_array('ssconvert', $tools, true)
			&& in_array($extension, self::GNUMERIC_FORMATS, true)) {
			// format=automatic *should* produce YYYY/MM/DD format
			// locale=fr_FR will make sure numbers are correctly formatted
			// sheet='$sheet_name' extracts only this sheet
			$cmd = 'ssconvert --export-type="Gnumeric_stf:stf_assistant" -O "format=automatic locale=fr_FR.UTF-8" %s %s %s 2>&1';
		}

		if ($cmd === null
			&& in_array('onlyoffice', $tools, true)) {
			return self::onlyoffice($source, $destination, $format, $extension);
		}

		if (in_array('unoconv', $tools, true)) {
			$cmd = 'unoconv %s -i FilterOptions=44,34,76 -o %3$s %2$s 2>&1';
		}
		elseif (in_array('unoconvert', $tools, true)) {
			// --filter-options PixelWidth=500 --filter-options PixelHeight=500
			// see https://github.com/unoconv/unoserver/issues/85
			// see https://github.com/unoconv/unoserver/issues/86
			$cmd = 'unoconvert %s --convert-to ' . escapeshellarg($format) . ' %s %s 2>&1';
		}

		if (!$cmd) {
			return false;
		}

		$cmd = sprintf($cmd,
			CONVERSION_TOOLS[$cmd]['args'] ?? '',
			Utils::escapeshellarg($source),
			Utils::escapeshellarg($destination)
		);

		return self::exec($cmd, $destination);
	}

	static protected function exec(string $cmd, string $destination): bool
	{
		$output = '';
		$code = null;
		$output = Utils::quick_exec($cmd, 5, $code);

		if ($code === 127) {
			throw new \LogicException(sprintf('Command "%s" failed as it wasn\'t found. Disable this command or install it. %s', $cmd, $output));
		}

		// Don't trust code as it can return != 0 even if generation was OK
		if (!file_exists($destination) || filesize($destination) < 10) {
			Utils::safe_unlink($destination);
			$e = new \RuntimeException($cmd . ' execution failed with code: ' . $code . "\n" . $output);

			// MuPDF can fail for a number of reasons: password protection, broken document, etc.
			// ffmpeg can fail if the file is a video format but with no video, etc.
			// So don't throw an error in these cases, just log it.
			// Or else we would get a lot of reported exceptions for weird files.
			ErrorManager::logException($e);
			return false;
		}

		return true;
	}

	static protected function mupdf(string $source, string $destination, string $format, string $extension): bool
	{
		if ($format === 'png') {
			// The single '1' at the end is to tell only to render the first page
			$cmd = 'mutool draw -i -N -q -F png -o %2$s -w 500 -h 500 -r 72 %1$s 1 2>&1';
		}
		elseif ($format === 'txt') {
			$cmd = 'mutool convert -F text -o %2$s %1$s';
		}
		else {
			throw new \InvalidArgumentException('MuPDF unsupported output format: ' . $format);
		}

		$link = null;

		// mupdf cannot correctly identify file type unless the correct file extension is used
		// @see https://bugs.ghostscript.com/show_bug.cgi?id=708002
		if (false === strpos(basename($source), '.')) {
			// Can't symlink: fail
			if (!function_exists('symlink') || PHP_OS_FAMILY == 'Windows') {
				return false;
			}

			// Sanity check, extension should be [a-z0-9]
			if (!ctype_alnum($extension)) {
				return false;
			}

			$link = $source . '.' . $extension;

			// Create a symlink containing the file extension
			if (!@symlink($source, $link)) {
				return false;
			}
		}

		$cmd = sprintf($cmd,
			Utils::escapeshellarg($link ?? $source),
			Utils::escapeshellarg($destination)
		);

		try {
			return self::exec($cmd, $destination);
		}
		finally {
			if ($link) {
				Utils::safe_unlink($link);
			}
		}
	}

	static protected function ffmpeg(string $source, string $destination): bool
	{
		$cmd = 'ffmpeg -hide_banner -loglevel error -ss 00:00:02 -i %s '
			. '-frames:v 1 -f image2 -c png -vf scale="min(900\, iw)":-1 %s 2>&1';

		$cmd = sprintf($cmd,
			Utils::escapeshellarg($source),
			Utils::escapeshellarg($destination)
		);

		return self::exec($cmd, $destination);
	}

	/**
	 * @see https://sdk.collaboraonline.com/docs/conversion_api.html
	 */
	static public function collabora(string $source, string $destination, string $format, string $mime): bool
	{
		if (null === WOPI_DISCOVERY_URL) {
			throw new \LogicException('Cannot convert with Collabora: WOPI_DISCOVERY_URL is not set');
		}

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

		$in = fopen($source, 'rb');
		$out = fopen($destination, 'wb');
		$body = [
			'format' => $format,
			'lang'   => 'fr-FR',
			'file'   => $in,
			//'options' => json_encode($options),
		];

		$r = (new HTTP)->request('POST', $url, $body, null, $out);

		fclose($in);
		fclose($out);

		if ($r->fail || $r->status !== 200 || @filesize($destination) < 10) {
			Utils::safe_unlink($destination);
			ErrorManager::logException(new \RuntimeException(sprintf('Conversion error with Collabora "%s": [%d] %s - %s', $url, $r->status, $r->error, $r->body)));
			return false;
		}

		return true;
	}

	static public function serveFromCache(string $id, string $token): void
	{
		if (substr($id, 0, 9) !== 'convert00' || !ctype_alnum($id)) {
			http_response_code(400);
			return;
		}

		$path = Static_Cache::getPath($id);

		if (Static_Cache::hasExpired($id)) {
			http_response_code(404);
			return;
		}

		$truth = hash_hmac('SHA1', $id . filemtime($path), LOCAL_SECRET_KEY);

		if (!hash_equals($truth, $token)) {
			http_response_code(403);
			return;
		}

		header('Content-Type: application/octet-stream', true);
		Server::serveFile(null, $path, null);
	}

	/**
	 * Convert a document using OnlyOffice API
	 * This works in 3 steps:
	 * 1. we send a POST request with a public URL
	 * 2. OnlyOffice downloads the URL and converts the document, then returns a new URL pointing to the converted document
	 * 3. We download the converted document from the supplied URL
	 *
	 * @see https://api.onlyoffice.com/docs/docs-api/additional-api/conversion-api/request/
	 */
	static public function onlyoffice(string $source, string $destination, string $format, string $ext): bool
	{
		if (null === WOPI_DISCOVERY_URL) {
			throw new \LogicException('Cannot convert with OnlyOffice: WOPI_DISCOVERY_URL is not set');
		}

		$token = CONVERSION_TOOLS['onlyoffice']['jwt_token'] ?? null;

		if (!$token) {
			throw new \LogicException('JWT Token is missing for OnlyOffice');
		}

		$id = 'convert00' . sha1(random_bytes(16));
		$path = Static_Cache::storeCopy($id, $source, new \DateTime('+5 minutes'));
		$t = hash_hmac('SHA1', $id . filemtime($path), LOCAL_SECRET_KEY);
		$file_url = ADMIN_URL . 'convert.php?i=' . $id . '&t=' . $t;

		$params = [
			'url'        => $file_url,
			'async'      => false,
			'filetype'   => $ext,
			'key'        => $id,
			'outputtype' => $format,
			'thumbnail'  => [
				'aspect' => 1,
				'first'  => true,
				'height' => 500,
				'width'  => 500,
			],
		];

		// Get URL
		$parts = parse_url(WOPI_DISCOVERY_URL);

		$url = sprintf('%s://%s%s/ConvertService.ashx',
			$parts['scheme'],
			$parts['host'],
			isset($parts['port']) ? ':' . $parts['port'] : ''
		);

		$b64 = fn($str) => str_replace('=', '', strtr(base64_encode($str), '+/', '-_'));
		$header = ['typ' => 'JWT', 'alg' => 'HS256'];

		// Create message
		$msg = $b64(json_encode($header, JSON_UNESCAPED_SLASHES))
			. '.' . $b64(json_encode($params, JSON_UNESCAPED_SLASHES));

		// Append signature
		$msg .= '.' . $b64(hash_hmac('SHA256', $msg, $token, true));
		$data = ['token' => $msg];

		try {
			$r = (new HTTP)->POST($url, $data, HTTP::JSON, ['Accept' => 'application/json']);

			if ($r->fail || $r->status !== 200) {
				return false;
			}

			$json = json_decode($r->body);

			if (!$json || !empty($json->error) || empty($json->fileUrl)) {
				$error = $json->error ?? '??';

				if ($error === -4) {
					$error .= ' -- URL was: ' . $file_url;
				}

				throw new \RuntimeException('Error returned by OnlyOffice: ' . $error);
			}

			// Download converted document from provided URL
			$r = (new HTTP)->download($json->fileUrl, $destination);

			if ($r->fail || $r->status !== 200) {
				return false;
			}

			return true;
		}
		finally {
			Static_Cache::remove($id);
		}
	}

	/**
	 * Convert a file to CSV if required
	 */
	static public function toCSVAuto(string $source): ?string
	{
		$mime = @mime_content_type($source);
		$ext = null;

		// XLSX
		if ($mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
			$ext = 'xlsx';
		}
		elseif ($mime == 'application/vnd.ms-excel') {
			$ext = 'xls';
		}
		elseif ($mime == 'application/vnd.oasis.opendocument.spreadsheet') {
			$ext = 'ods';
		}
		// Assume raw CSV
		elseif (preg_match('!/csv|text/!', $mime)) {
			return $source;
		}

		if (!$ext || !self::canConvert($ext, 'csv')) {
			return null;
		}

		// Only keep CSV file for a short time
		$id = 'csv_' . md5(random_bytes(10));
		$id2 = null;
		$destination = Static_Cache::create($id, new \DateTime('+5 minutes'));

		if (is_uploaded_file($source)) {
			$id2 = $id . '_in';
			$new_path = Static_Cache::create($id . '_in', new \DateTime('+5 minutes'));
			move_uploaded_file($source, $new_path);
			$source = $new_path;
		}

		$ok = self::convert($source, $destination, 'csv', filesize($source), $mime);

		if (!$ok) {
			if ($id2) {
				Static_Cache::remove($id2);
			}

			throw new UserException(sprintf('La conversion du fichier depuis le format "%s" a échoué', $ext));
		}

		return $destination;
	}
}
