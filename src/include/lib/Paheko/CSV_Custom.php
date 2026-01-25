<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Files\Conversion;
use Paheko\Files\Files;

use KD2\Office\Calc\Reader as ODS_Reader;
use KD2\Office\Excel\Reader as XLSX_Reader;

/**
 * Allows to parse CSV/spreadsheet files and select matching columns
 * Step 1: load CSV/XLSX/ODS file
 * Step 2: if the file has more than one sheet (XLSX/ODS), select sheet
 * Step 3: set columns matching
 * Step 4: iterate over CSV
 *
 * The file and settings are stored in static file cache between steps.
 */
class CSV_Custom
{
	protected ?Session $session;
	protected ?string $key;

	/**
	 * List of sheets (should be NULL for CSV)
	 * (int) index => (string) label
	 */
	protected ?array $sheets = null;

	/**
	 * List of rows, each index is an integer matching the sheet
	 * (int) => (array) [(array) row1, (array) row2...]
	 */
	protected ?array $rows = null;

	/**
	 * List of columns that can be used
	 * key => label, eg. 'amount' => 'Transaction amount'
	 */
	protected array $columns;

	protected array $columns_defaults;

	/**
	 * List of mandatory columns (just the key, eg. ['amount'])
	 */
	protected array $mandatory_columns = [];

	/**
	 * Selected sheet index
	 */
	protected ?int $sheet = null;

	/**
	 * Translation table between CSV columns and columns keys
	 */
	protected ?array $translation = null;

	/**
	 * Number of lines to skip at the beginning of the file
	 */
	protected int $skip = 1;

	/**
	 * Modifier callback, will be used to modify each line
	 */
	protected $modifier = null;

	/**
	 * Max file size
	 * This is mostly to avoid exhausting the memory
	 */
	protected int $max_file_size = 1024*1024*15;

	/**
	 * Name of the loaded file
	 */
	protected ?string $file_name = null;

	/**
	 * Whether to allow or forbid the selection of a sheet
	 * If false, then the first sheet will be used
	 * This is mostly because some existing forms don't have the necessary code for selecting
	 * a sheet after file upload.
	 */
	protected bool $sheet_selection = false;

	/**
	 * List of object properties that need to be cached between steps
	 */
	protected array $cache_properties = ['rows', 'translation', 'skip', 'file_name', 'sheets', 'sheet'];

	protected ?string $cache_key = null;
	protected ?array $_default = null;

	public function __construct(?Session $session = null, ?string $key = null)
	{
		$this->session = $session;
		$this->key = $key;
		$id = $session ? $session::getUserId() : null;
		$id ??= 'anon';
		$this->cache_key = $id . '_' . $key;

		if ($this->cache_key && !Static_Cache::hasExpired($this->cache_key)) {
			$data = Static_Cache::import($this->cache_key);

			foreach ($this->cache_properties as $key) {
				$this->$key = $data[$key];
			}
		}
	}

	public function __destruct()
	{
		if ($this->session && $this->cache_key && ($this->rows || $this->translation || $this->skip !== 1)) {
			$data = [];

			foreach ($this->cache_properties as $key) {
				$data[$key] = $this->$key;
			}

			Static_Cache::export($this->cache_key, $data, new \DateTime('+3 hours'));
			Static_Cache::prune();
		}
	}

	/**
	 * Upload a file from the browser
	 */
	public function upload(?array $file): void
	{
		if (empty($file['size']) || empty($file['tmp_name']) || empty($file['name'])) {
			throw new UserException('Fichier invalide, ou aucun fichier fourni');
		}

		$path = $file['tmp_name'];

		if (!is_uploaded_file($path)) {
			throw new \LogicException('Not an uploaded file: ' . $path);
		}

		$this->loadFile($path, $file['name']);

		@unlink($path);
	}

	/**
	 * Load from a file in the local file storage
	 */
	public function loadFromStoredFile(string $path): void
	{
		$file = Files::get($path);

		if (!$file) {
			throw new \InvalidArgumentException('Chemin invalide : ce fichier source n\'existe pas');
		}

		$path = $file->getLocalOrCacheFilePath();

		if (!$path) {
			throw new \LogicException('File contents not found');
		}

		$file_name = Utils::basename($path);
		$this->loadFile($path, $file_name);
	}

	public function canConvert(): bool
	{
		return Conversion::canConvertToCSV();
	}

	/**
	 * Load XLSX / ODS file, including support for multiple sheets
	 */
	public function loadSpreadsheetFile(string $type, string $path, ?string $file_name = null): void
	{
		$class = sprintf('\\KD2\\Office\\%s\\Reader', $type);

		try {
			$s = new $class;
			$s->openFile($path);

			$sheets = $s->listSheets();

			if (count($sheets) > 1) {
				$this->sheets = $sheets;
			}
			else {
				$this->sheet = 0;
			}

			$this->rows = [];

			foreach ($sheets as $i => $name) {
				$this->rows[$i] = iterator_to_array($s->iterate($i));
			}

			if (!$this->sheet_selection) {
				$this->sheet = $s->getActiveSheet();
			}
		}
		catch (\Exception $e) {
			// FIXME: remove debug copy
			copy($path, sys_get_temp_dir() . '/spreadsheet_error_' . date('Ymd_His'));
			throw $e;
		}

		unset($s);
	}

	/**
	 * Load CSV file
	 */
	public function loadFile(string $path, ?string $file_name = null): void
	{
		if (filesize($path) > $this->max_file_size) {
			throw new UserException(sprintf('Ce fichier est trop gros (taille maximale : %s)', Utils::format_bytes($this->max_file_size)));
		}

		$ext = substr($file_name, strrpos($file_name, '.')+1);

		$this->rows = null;
		$this->sheets = null;
		$this->sheet = null;

		if ($ext === 'xlsx' || $ext === 'ods') {
			$this->loadSpreadsheetFile($ext === 'xlsx' ? 'Excel' : 'Calc', $path, $file_name);
			return;
		}
		// Automatically convert from legacy XLS files
		elseif ($ext === 'xls' && $this->canConvert()) {
			$path = Conversion::toCSVAuto($path);
		}

		if (!$path) {
			throw new UserException('Ce fichier n\'est pas dans un format accepté.');
		}

		$rows = [];

		foreach (CSV::iterate($path) as $line => $row) {
			if ($line > 1
				&& count($rows[$line - 1]) !== count($row)) {
				throw new UserException(sprintf('Ligne %d : le nombre de colonne diffère de la ligne précédente, cela peut indiquer un fichier corrompu ou comportant plusieurs feuilles différentes', $line));
			}

			$rows[$line] = $row;
		}

		if (!count($rows)) {
			throw new UserException('Ce fichier est vide (aucune ligne trouvée).');
		}

		// Only one sheet
		$this->rows = [$rows];
		$this->sheet = 0;
		$this->file_name = $file_name;
	}

	/**
	 * Allow for special parsing of lines (in iterate), here nothing is done
	 * @return int 1 for adding row to output, -1 to stop the loop, 0 to ignore the line
	 */
	protected function parseLine(int $line, array &$row): int
	{
		return 1;
	}

	/**
	 * Add a new line to the current sheet
	 */
	public function append(array $row): void
	{
		if (empty($this->rows[$this->sheet])) {
			// Start array at one, not zero
			$this->rows[$this->sheet] = [1 => $row];
		}
		else {
			$this->rows[$this->sheet][] = $row;
		}
	}

	/**
	 * Add a new line at the beginning of the current sheet,
	 * this will re-number all the lines
	 */
	public function prepend(array $row): void
	{
		array_unshift($this->rows[$this->sheet], $row);

		// Re-number array to start at one, not zero
		$this->rows[$this->sheet] = array_combine(range(1, count($this->rows[$this->sheet])), array_values($this->rows[$this->sheet]));
	}

	/**
	 * Iterate over each line of the current sheet
	 */
	public function iterate(): \Generator
	{
		if (empty($this->rows[$this->sheet])) {
			throw new \LogicException('No file has been loaded');
		}

		if (!$this->columns || !$this->translation) {
			throw new \LogicException('Missing columns or translation table');
		}

		$i = 0;

		foreach ($this->rows[$this->sheet] as $line => $row) {
			if ($i++ < $this->skip) {
				continue;
			}

			$r = $this->parseLine($line, $row);

			if ($r === -1) {
				break;
			}
			elseif ($r === 0) {
				continue;
			}

			yield $line => $this->getLine($line, $row);
		}
	}

	/**
	 * Return a specific line of the sheet, transformed with keys and default values
	 */
	public function getLine(int $i, ?array $row = null): ?\stdClass
	{
		if (null === $row && !isset($this->rows[$this->sheet][$i])) {
			return null;
		}

		$this->_default ??= array_fill_keys($this->translation, null);
		$row ??= $this->rows[$this->sheet][$i];
		$row_with_defaults = $this->_default;

		foreach ($row as $col => $value) {
			if (!isset($this->translation[$col])) {
				continue;
			}

			$row_with_defaults[$this->translation[$col]] = trim((string)$value);
		}

		$row = (object) $row_with_defaults;

		if (null !== $this->modifier) {
			try {
				$row = call_user_func($this->modifier, $row);
			}
			catch (UserException $e) {
				throw new UserException(sprintf('Ligne %d : %s', $i, $e->getMessage()));
			}
		}

		return $row;
	}

	/**
	 * Return first line (raw)
	 */
	public function getFirstLine(): array
	{
		if (!$this->loaded()) {
			throw new \LogicException('No file has been loaded');
		}

		return current($this->rows[$this->sheet]);
	}

	public function setModifier(callable $callback): void
	{
		$this->modifier = $callback;
	}

	public function searchColumn(string $str, array $columns)
	{
		foreach ($columns as $key => $value) {
			$columns[$key] = mb_strtolower(str_replace('’', '\'', $value));
		}

		$str = mb_strtolower(str_replace('’', '\'', $str));

		return array_search($str, $columns, true);
	}

	/**
	 * Return list of columns with the match, either because it has been selected
	 * by the user, or because the name or the key is matching (auto-magic)
	 */
	public function getSelectedTable(?array $source = null): array
	{
		if (null === $source && isset($_POST['translation_table'])) {
			$source = $_POST['translation_table'];
		}
		elseif (null === $source) {
			$source = [];
		}

		$selected = $this->getFirstLine();

		foreach ($selected as $i => &$v) {
			if (isset($source[$i])) {
				$v = $source[$i];
			}
			elseif (isset($this->translation[$i])) {
				$v = $this->translation[$i];
			}
			// Match by key: code_postal === code_postal
			elseif (array_key_exists($v, $this->columns)) {
				// $v is already good, do nothing
			}
			// Match by label: Code postal === Code postal
			elseif ($found = $this->searchColumn($v, $this->columns)) {
				$v = $found;
			}
			elseif ($found = $this->searchColumn($v, $this->columns_defaults)) {
				$v = $found;
			}
			else {
				$v = null;
			}
		}

		return $selected;
	}

	public function getTranslationTable(): ?array
	{
		return $this->translation;
	}

	/**
	 * Try to automatically set translation table by matching labels and keys, if possible
	 */
	public function setTranslationTableAuto(): void
	{
		$sel = $this->getSelectedTable([]);
		$this->setTranslationTable($sel);
	}

	/**
	 * Save translation table
	 */
	public function setTranslationTable(array $table): void
	{
		if (!count($table)) {
			throw new UserException('Aucune colonne n\'a été sélectionnée');
		}

		$translation = [];

		foreach ($table as $csv => $target) {
			if (empty($target)) {
				continue;
			}

			if (!array_key_exists($target, $this->columns)) {
				throw new UserException('Colonne inconnue: ' . $target);
			}

			$translation[(int)$csv] = $target;
		}

		$this->setIndexedTable($translation);
	}

	/**
	 * Return true if a column (from its key) has been selected in the translation table
	 */
	public function hasSelectedColumn(string $column): bool
	{
		return in_array($column, $this->translation, true);
	}

	public function setIndexedTable(array $table): void
	{
		if (!count($table)) {
			throw new UserException('Aucune colonne n\'a été sélectionnée');
		}

		foreach ($this->getMandatoryColumns() as $column) {
			// Either one of these columns is mandatory
			if (is_array($column)) {
				$found = false;
				$names = [];

				foreach ($column as $c) {
					foreach ((array) $c as $key) {
						if (in_array($key, $table, true)) {
							$found = true;
							break;
						}

						$names[] = $this->columns[$key];
					}
				}

				if (!$found) {
					$names = array_map(fn($a) => '"' . $a . '"', $names);
					throw new UserException(sprintf('Une des colonnes (%s) est obligatoire, mais aucune n\'a été sélectionnée ou n\'existe.', implode(', ', $names)));
				}
			}
			elseif (!in_array($column, $table, true)) {
				throw new UserException(sprintf('La colonne "%s" est obligatoire mais n\'a pas été sélectionnée ou n\'existe pas.', $this->columns[$column]));
			}
		}

		$this->translation = $table;
	}

	public function clear(): void
	{
		$this->rows = null;
		$this->sheets = null;
		$this->sheet = 0;
		$this->translation = null;
		$this->skip = 1;

		if ($this->cache_key) {
			Static_Cache::remove($this->cache_key);
		}
	}

	/**
	 * Return true if the CSV is loaded
	 */
	public function loaded(): bool
	{
		return null !== $this->rows;
	}

	/**
	 * Return true if the CSV is ready for processing: loaded, sheet selected, and translation table set
	 */
	public function ready(): bool
	{
		return $this->loaded() && $this->isSheetSelected() && !empty($this->translation);
	}

	public function isSheetSelected(): bool
	{
		return isset($this->sheet);
	}

	public function count(?int $sheet = null): ?int
	{
		$sheet ??= $this->sheet;

		return null !== $this->rows ? count($this->rows[$sheet]) : null;
	}

	public function skip(int $count): void
	{
		$this->skip = $count;
	}

	public function getSkippedLines(): int
	{
		return $this->skip;
	}

	public function setColumns(array $columns, array $defaults = []): void
	{
		$this->columns = array_filter($columns);
		$this->columns_defaults = array_filter($defaults);
	}

	public function setMandatoryColumns(array $columns): void
	{
		$this->mandatory_columns = $columns;
	}

	public function setMaxFileSize(int $size)
	{
		$this->max_file_size = $size;
	}

	public function getColumnsString(): string
	{
		if (!empty($this->columns_defaults)) {
			$c = array_intersect_key($this->columns_defaults, $this->columns);
		}
		else {
			$c = $this->columns;
		}

		return implode(', ', $c);
	}

	protected function getColumnNamesFromArray(array $labels, array $selected)
	{
		$names = [];

		foreach ($selected as $column) {
			if (is_array($column)) {
				$list = [];

				foreach ($column as $item) {
					$column_labels = [];

					// In case an alternative key is a list of keys
					foreach ((array)$item as $key) {
						$column_labels[] = $labels[$key];
					}

					$list[] = implode(' et ', $column_labels);
				}

				$names[] = implode(' ou ', $list);
				unset($list, $item, $column_labels);
			}
			else {
				$names[] = $labels[$column];
			}
		}

		return implode(', ', $names);
	}

	public function getMandatoryColumnsString(): string
	{
		if (!empty($this->columns_defaults)) {
			$labels = array_intersect_key($this->columns_defaults, $this->columns);
		}
		else {
			$labels = $this->columns;
		}

		return $this->getColumnNamesFromArray($labels, $this->getMandatoryColumns());
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function getColumnLabel(string $key): ?string
	{
		return $this->columns[$key] ?? null;
	}

	public function getColumnsWithDefaults(): array
	{
		$out = [];

		foreach ($this->columns as $key => $label) {
			$out[] = compact('key', 'label') + ['match' => $this->columns_defaults[$key] ?? $label];
		}

		return $out;
	}

	public function getMandatoryColumns(): array
	{
		return $this->mandatory_columns;
	}

	public function export(): array
	{
		return [
			'loaded'            => $this->loaded(),
			'ready'             => $this->ready(),
			'count'             => $this->count(),
			'skip'              => $this->skip,
			'columns'           => $this->columns,
			'mandatory_columns' => $this->mandatory_columns,
			'translation_table' => $this->translation,
			'rows'              => $this->ready() ? iterator_to_array($this->iterate()) : null,
			'header'            => $this->getHeader(),
			'file_name'         => $this->file_name,
		];
	}

	public function getHeader(): ?array
	{
		if (!$this->ready()) {
			return null;
		}

		$out = [];

		foreach ($this->translation as $name) {
			$out[$name] = $this->columns[$name];
		}

		return $out;
	}

	public function getRawHeader(): ?array
	{
		if (!$this->loaded()) {
			return null;
		}

		return reset($this->rows[$this->sheet]) ?: null;
	}

	public function hasRawHeaderColumn(string $label): bool
	{
		return in_array($label, $this->getRawHeader(), true);
	}

	/**
	 * Reorder with a specific column
	 */
	public function orderBy(string $column): void
	{
		$col = array_search($column, $this->translation, true);

		if ($col === false) {
			throw new \InvalidArgumentException('Unknown column: ' . $column);
		}

		$header = array_slice($this->rows[$this->sheet], 0, $this->skip);
		$rows = array_slice($this->rows[$this->sheet], $this->skip);

		usort($rows, fn($a, $b) => strcmp($a[$col] ?? '', $b[$col] ?? ''));

		$rows = $header + $rows;

		// Renumber array
		$this->rows[$this->sheet] = array_combine(range(1, count($rows)), array_values($rows));
	}

	public function listSheets(): array
	{
		return $this->sheets ?? [];
	}

	public function setSheet(int $i): void
	{
		if (!array_key_exists($i, $this->sheets)) {
			throw new \InvalidArgumentException('Unknown sheet #' . $i);
		}

		$this->sheet = $i;
	}

	public function toggleSheetSelection(bool $enable): void
	{
		$this->sheet_selection = $enable;
	}

	public function runForm(Form $form, string $csrf_key): void
	{
		$url = Utils::getSelfURI();

		$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () {
			$this->upload($_FILES['file']);
		}, $csrf_key, $url);

		if ($this->sheet_selection) {
			$form->runIf('set_sheet', function () {
				$this->setSheet(intval($_POST['sheet'] ?? 0));
			}, $csrf_key, $url);
		}

		$form->runIf('set_columns', function () {
			$this->skip(intval($_POST['skip_first_line'] ?? 0));
			$this->setTranslationTable($_POST['translation_table'] ?? []);
		}, $csrf_key, $url);
	}
}
