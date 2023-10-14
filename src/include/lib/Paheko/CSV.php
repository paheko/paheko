<?php

namespace Paheko;

use KD2\Office\Calc\Writer as ODSWriter;

use KD2\HTML\TableExport;

class CSV
{
	/**
	 * Convert a file to CSV if required (and if CALC_CONVERT_COMMAND is set)
	 */
	static public function convertUploadIfRequired(string $path, bool $delete_original = false): string
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

			self::convertXLSX($a, $b);

			return $b;
		}
		finally {
			if ($delete_original) {
				@unlink($a);
			}
		}
	}

	static public function convertXLSX(string $from, string $to): string
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
		else {
			throw new \LogicException(sprintf('Conversion tool "%s" is not supported', $tool));
		}

		$cmd = sprintf($cmd, Utils::escapeshellarg($from), Utils::escapeshellarg($to));
		$cmd .= ' 2>&1';
		$return = shell_exec($cmd);

		if (!file_exists($to)) {
			throw new UserException('Impossible de convertir le fichier. Vérifier que le fichier est un format supporté.');
		}

		return $to;
	}

	static public function supportsXLSExport(): bool
	{
		return CALC_CONVERT_COMMAND ? true : false;
	}

	static public function readAsArray(string $path)
	{
		if (!file_exists($path) || !is_readable($path))
		{
			throw new \RuntimeException('Fichier inconnu : '.$path);
		}

		$fp = self::open($path);

		if (!$fp)
		{
			return false;
		}

		$delim = self::findDelimiter($fp);
		self::skipBOM($fp);

		$line = 0;
		$out = [];
		$nb_columns = null;

		while (!feof($fp))
		{
			$row = fgetcsv($fp, 4096, $delim);
			$line++;

			if (empty($row))
			{
				continue;
			}

			if (null === $nb_columns)
			{
				$nb_columns = count($row);
			}

			if (count($row) != $nb_columns)
			{
				throw new UserException('Erreur sur la ligne ' . $line . ' : incohérence dans le nombre de colonnes avec la première ligne.');
			}

			// Make sure the data is UTF-8 encoded
			$row = array_map(fn ($a) => Utils::utf8_encode(trim($a)), $row);

			$out[$line] = $row;
		}

		fclose($fp);

		return $out;
	}

	static public function open(string $file)
	{
		return fopen($file, 'r');
	}

	static public function findDelimiter(&$fp)
	{
		$line = '';

		while ($line === '' && !feof($fp))
		{
			$line = fgets($fp, 4096);
		}

		if (strlen($line) >= 4095) {
			throw new UserException('Fichier CSV illisible : la première ligne est trop longue.');
		}

		// Delete the columns content
		$line = preg_replace('/".*?"/', '', $line);

		$delims = [
			';' => substr_count($line, ';'),
			',' => substr_count($line, ','),
			"\t"=> substr_count($line, "\t"),
			'|' => substr_count($line, '|'),
		];

		arsort($delims);
		reset($delims);

		rewind($fp);

		return key($delims);
	}

	static public function skipBOM(&$fp)
	{
		// Skip BOM
		if (fgets($fp, 4) !== chr(0xEF) . chr(0xBB) . chr(0xBF))
		{
			fseek($fp, 0);
		}
	}

	static public function row($row): string
	{
		$row = (array) $row;

		array_walk($row, function (&$field) {
			$field = strtr((string) $field, ['"' => '""', "\r\n" => "\n"]);
		});

		return sprintf("\"%s\"\r\n", implode('","', $row));
	}

	static public function export(string $format, string $name, iterable $iterator, ?array $header = null, ?callable $row_map_callback = null): void
	{
		// Flush any previous output, such as module HTML code etc.
		@ob_end_clean();

		if ('csv' == $format) {
			self::toCSV(... array_slice(func_get_args(), 1));
		}
		elseif ('xlsx' == $format && CALC_CONVERT_COMMAND) {
			self::toXLSX(... array_slice(func_get_args(), 1));
		}
		elseif ('ods' == $format) {
			self::toODS(... array_slice(func_get_args(), 1));
		}
		else {
			throw new \InvalidArgumentException('Unknown export format');
		}
	}

	static public function exportHTML(string $format, string $html, string $name = 'Export'): void
	{
		$css = file_get_contents(ROOT . '/www/admin/static/styles/06-tables-export.css');
		TableExport::download($format, $name, $html, $css);
		exit;
	}

	static protected function rowToArray($row, ?callable $row_map_callback)
	{
		if (null !== $row_map_callback) {
			call_user_func_array($row_map_callback, [&$row]);
		}

		if (is_object($row) && $row instanceof Entity) {
			$row = $row->asArray();
		}
		elseif (is_object($row)) {
			$row = (array) $row;
		}

		foreach ($row as $key => &$v) {
			if ((is_object($v) && !($v instanceof \DateTimeInterface)) || is_array($v)) {
				throw new \UnexpectedValueException(sprintf('Unexpected value for "%s": %s', $key, gettype($v)));
			}
		}

		return $row;
	}

	static public function toCSV(string $name, iterable $iterator, ?array $header = null, ?callable $row_map_callback = null, string $output = null): void
	{
		if (null === $output) {
			header('Content-type: application/csv');
			header(sprintf('Content-Disposition: attachment; filename="%s.csv"', $name));

			$fp = fopen('php://output', 'w');
		}
		else {
			$fp = fopen($output, 'w');
		}

		if ($header) {
			fputs($fp, self::row($header));
		}

		if (!($iterator instanceof \Iterator) || $iterator->valid()) {
			foreach ($iterator as $row) {
				$row = self::rowToArray($row, $row_map_callback);

				foreach ($row as $key => &$v) {
					if (is_object($v) && $v instanceof \DateTimeInterface) {
						if ($v->format('His') == '000000') {
							$v = $v->format('d/m/Y');
						}
						else {
							$v = $v->format('d/m/Y H:i:s');
						}
					}
				}

				if (!$header)
				{
					fputs($fp, self::row(array_keys($row)));
					$header = true;
				}

				fputs($fp, self::row($row));
			}
		}

		fclose($fp);
	}

	static public function toODS(string $name, iterable $iterator, ?array $header = null, ?callable $row_map_callback = null, string $output = null): void
	{
		if (null === $output) {
			header('Content-type: application/vnd.oasis.opendocument.spreadsheet');
			header(sprintf('Content-Disposition: attachment; filename="%s.ods"', $name));
		}

		$ods = new ODSWriter;
		$ods->table_name = $name;

		if ($header) {
			$ods->add((array) $header);
		}

		if (!($iterator instanceof \Iterator) || $iterator->valid()) {
			foreach ($iterator as $row) {
				$row = self::rowToArray($row, $row_map_callback);

				if (!$header)
				{
					$ods->add(array_keys($row));
					$header = true;
				}

				$ods->add((array) $row);
			}
		}

		$ods->output($output);
	}

	static public function toXLSX(string $name, iterable $iterator, ?array $header = null, ?callable $row_map_callback = null): void
	{
		if (!CALC_CONVERT_COMMAND) {
			throw new \LogicException('CALC_CONVERT_COMMAND is not set');
		}

		$tmpfile1 = sprintf('%s/export_%s.ods', STATIC_CACHE_ROOT, md5(random_bytes(10)));
		$tmpfile2 = substr($tmpfile1, 0, -3) . 'xlsx';

		try {
			self::toODS($name, $iterator, $header, $row_map_callback, $tmpfile1);

			self::convertXLSX($tmpfile1, $tmpfile2);

			header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header(sprintf('Content-Disposition: attachment; filename="%s.xlsx"', $name));

			readfile($tmpfile2);
		}
		finally {
			@unlink($tmpfile1);
			@unlink($tmpfile2);
		}
	}

	static public function importUpload(array $file, array $expected_columns): \Generator
	{
		if (empty($file['size']) || empty($file['tmp_name'])) {
			throw new UserException('Fichier invalide');
		}

		return self::import($file['tmp_name'], $expected_columns);
	}

	static public function import(string $file, ?array $columns = null, array $required_columns = []): \Generator
	{
		$delete_after = is_uploaded_file($file);
		$file = self::convertUploadIfRequired($file, $delete_after);

		try {
			$fp = fopen($file, 'r');

			if (!$fp) {
				throw new UserException('Le fichier ne peut être ouvert');
			}

			// Find the delimiter
			$delim = self::findDelimiter($fp);
			self::skipBOM($fp);

			$line = 0;

			$header = fgetcsv($fp, 4096, $delim);

			// Make sure the data is UTF-8 encoded
			$header = array_map(fn ($a) => Utils::utf8_encode(trim($a)), $header);

			$columns_map = [];

			if (null === $columns) {
				$columns_map = $header;
			}
			else {
				$columns_is_list = is_int(key($columns));

				// Check for columns
				foreach ($header as $key => $label) {
					// try to find with string key
					if (!$columns_is_list && array_key_exists($label, $columns)) {
						$columns_map[] = $label;
					}
					// Or with label
					elseif (in_array($label, $columns)) {
						$columns_map[] = $columns_is_list ? $label : array_search($label, $columns);
					}
					else {
						$columns_map[] = null;
					}
				}
			}

			foreach ($required_columns as $key) {
				if (!in_array($key, $columns_map)) {
					throw new UserException(sprintf('La colonne "%s" est absente du fichier importé', $columns[$key] ?? $key));
				}
			}

			while (!feof($fp))
			{
				$row = fgetcsv($fp, 4096, $delim);
				$line++;

				// Empty line, skip
				if (empty($row)) {
					continue;
				}

				if (count($row) != count($header))
				{
					throw new UserException('Erreur sur la ligne ' . $line . ' : le nombre de colonnes est incorrect.');
				}

				// Make sure the data is UTF-8 encoded
				$row = array_map(fn ($a) => Utils::utf8_encode(trim($a)), $row);

				$row = array_combine($columns_map, $row);

				yield $line => $row;
			}

			fclose($fp);
		}
		finally {
			if ($delete_after) {
				@unlink($file);
			}
		}
	}
}