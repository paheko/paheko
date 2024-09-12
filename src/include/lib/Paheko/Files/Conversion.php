<?php

namespace Paheko\Files;

use const Paheko\{WOPI_DISCOVERY_URL, CALC_CONVERT_COMMAND, CACHE_ROOT};
use Paheko\Utils;

class Conversion
{
	static public function collabora(string $source, string $destination, string $format, ?string $name = null, ?string $mime = null): bool
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

		$mime ??= mime_content_type($source);
		$name ??= basename($source);
		$ext = substr($name, strrpos($name, '.') + 1);
		$name = md5($name) . '.' . ($ext ?: 'unknown');

		$curl = \curl_init($url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, [
			'format' => $format,
			//'options' => json_encode($options),
			'file' => new \CURLFile($source, $mime, $name),
		]);

		$fp = fopen($destination, 'wb');
		curl_setopt($curl, CURLOPT_FILE, $fp);

		curl_exec($curl);
		$info = curl_getinfo($curl);

		if ($error = curl_error($curl)) {
			Utils::safe_unlink($destination);
			ErrorManager::reportExceptionSilent(new \RuntimeException(sprintf('cURL error on "%s": %s', $url, $error)));
			return false;
		}

		curl_close($curl);
		fclose($fp);
		unset($curl);

		if (($code = $info['http_code']) != 200 || @filesize($destination) < 10) {
			Utils::safe_unlink($destination);
			ErrorManager::reportExceptionSilent(new \RuntimeException('Cannot convert with Collabora: code ' . $code . "\n" . json_encode($info)));
			return false;
		}

		return true;
	}

	/**
	 * Convert a file to CSV if required (and if CALC_CONVERT_COMMAND is set)
	 */
	static public function toCSVAuto(string $path, bool $delete_original = false): string
	{
		if (!CALC_CONVERT_COMMAND) {
			return $path;
		}

		$mime = @mime_content_type($path);

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
		else {
			return $path;
		}

		$r = md5(random_bytes(10));
		$a = sprintf('%s/convert_%s.%s', CACHE_ROOT, $r, $ext);
		$b = sprintf('%s/convert_%s.csv', CACHE_ROOT, $r);
		$is_upload = is_uploaded_file($path);

		try {
			if ($is_upload) {
				move_uploaded_file($path, $a);
			}
			else {
				copy($path, $a);
			}

			self::toCSV($a, $b);

			return $b;
		}
		finally {
			if ($delete_original) {
				@unlink($a);
			}
		}
	}

	static public function toCSV(string $from, string $to): string
	{
		$tool = substr(CALC_CONVERT_COMMAND, 0, strpos(CALC_CONVERT_COMMAND, ' ') ?: strlen(CALC_CONVERT_COMMAND));

		if ($tool == 'unoconv') {
			$cmd = CALC_CONVERT_COMMAND . ' -i FilterOptions=44,34,76 -o %2$s %1$s';
		}
		elseif ($tool == 'ssconvert') {
			$cmd = CALC_CONVERT_COMMAND . ' %1$s %2$s';
		}
		elseif ($tool == 'unoconvert') {
			$cmd = CALC_CONVERT_COMMAND . ' %1$s %2$s';
		}
		elseif ($tool === 'collabora') {
			$ok = self::collabora($from, $to, 'csv');
			$cmd = null;
		}
		else {
			throw new \LogicException(sprintf('Conversion tool "%s" is not supported', $tool));
		}

		if ($cmd !== null) {
			$cmd = sprintf($cmd, Utils::escapeshellarg($from), Utils::escapeshellarg($to));
			$cmd .= ' 2>&1';
			Utils::quick_exec($cmd, 10);
		}

		if (!file_exists($to)) {
			throw new UserException('Impossible de convertir le fichier. Vérifier que le fichier est un format supporté.');
		}

		return $to;
	}
}
