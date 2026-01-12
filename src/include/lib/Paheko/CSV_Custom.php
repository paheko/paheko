<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Files\Conversion;
use Paheko\Files\Files;

class CSV_Custom
{
	protected ?Session $session;
	protected ?string $key;
	protected ?array $csv = null;
	protected ?array $translation = null;
	protected array $columns;
	protected array $columns_defaults;
	protected array $mandatory_columns = [];
	protected int $skip = 1;
	protected $modifier = null;
	protected ?array $_default = null;
	protected ?string $cache_key = null;
	protected int $max_file_size = 1024*1024*10;
	protected ?string $file_name = null;
	protected array $cache_properties = ['csv', 'translation', 'skip', 'file_name'];

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
		if ($this->session && $this->cache_key && ($this->csv || $this->translation || $this->skip !== 1)) {
			$data = [];

			foreach ($this->cache_properties as $key) {
				$data[$key] = $this->$key;
			}

			Static_Cache::export($this->cache_key, $data, new \DateTime('+3 hours'));
			Static_Cache::prune();
		}
	}

	public function upload(?array $file): void
	{
		if (empty($file['size']) || empty($file['tmp_name']) || empty($file['name'])) {
			throw new UserException('Fichier invalide, ou aucun fichier fourni');
		}

		$path = $file['tmp_name'];

		$this->loadFile($path, $file['name']);

		@unlink($path);
	}

	public function loadFromStoredFile(string $path): void
	{
		$file = Files::get($path);

		if (!$file) {
			throw new \InvalidArgumentException('Chemin invalide : ce fichier source n\'existe pas');
		}

		$this->file_name = Utils::basename($path);

		$path = $file->getLocalOrCacheFilePath();

		if (!$path) {
			throw new \LogicException('File contents not found');
		}

		$this->loadFile($path);
	}

	public function canConvert(): bool
	{
		return Conversion::canConvertToCSV();
	}

	public function loadFile(string $path, string $file_name): void
	{
		$ext = strtolower(substr($file_name, -4));

		// Automatically convert from XLSX/XLS/ODS/etc.
		if ($ext !== '.csv' && $this->canConvert()) {
			$path = Conversion::toCSVAuto($path);
		}

		if (!$path) {
			throw new UserException('Ce fichier n\'est pas dans un format accepté.');
		}

		if (filesize($path) > $this->max_file_size) {
			throw new UserException(sprintf('Ce fichier CSV est trop gros (taille maximale : %s)', Utils::format_bytes($this->max_file_size)));
		}

		$this->csv = null;
		$rows = [];
		$prev = null;
		$i = 1;

		foreach (CSV::iterate($path) as $line => $row) {
			$r = $this->parseLine($line, $row);

			if (-1 === $r) {
				break;
			}
			elseif (0 === $r) {
				continue;
			}

			if (null !== $prev
				&& count($prev) !== count($row)) {
				throw new UserException(sprintf('Ligne %d : le nombre de colonne diffère de la ligne précédente, cela peut indiquer un fichier corrompu ou comportant plusieurs feuilles différentes', $line));
			}

			$rows[$i++] = $row;
			$prev = $row;
		}

		if (!count($rows)) {
			throw new UserException('Ce fichier est vide (aucune ligne trouvée).');
		}

		$this->csv = $rows;
		$this->file_name = $file_name;
	}

	/**
	 * Allow for special parsing, here nothing is done
	 * @return int 1 for adding row to output, -1 to stop the loop, 0 to ignore the line
	 */
	protected function parseLine(int $line, array &$row): int
	{
		return 1;
	}

	public function append(array $row): void
	{
		if (empty($this->csv)) {
			// Start array at one, not zero
			$this->csv = [1 => $row];
		}
		else {
			$this->csv[] = $row;
		}
	}

	public function prepend(array $row): void
	{
		array_unshift($this->csv, $row);

		// Re-number array to start at one, not zero
		$this->csv = array_combine(range(1, count($this->csv)), array_values($this->csv));
	}

	public function iterate(): \Generator
	{
		if (empty($this->csv)) {
			throw new \LogicException('No file has been loaded');
		}

		if (!$this->columns || !$this->translation) {
			throw new \LogicException('Missing columns or translation table');
		}

		$i = 0;

		foreach ($this->csv as $line => $row) {
			if ($i++ < $this->skip) {
				continue;
			}

			yield $line => $this->getLine($line, $row);
		}
	}

	public function getLine(int $i, ?array $row = null): ?\stdClass
	{
		if (!isset($this->csv[$i])) {
			return null;
		}

		$this->_default ??= array_fill_keys($this->translation, null);
		$row ??= $this->csv[$i];
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

	public function getFirstLine(): array
	{
		if (!$this->loaded()) {
			throw new \LogicException('No file has been loaded');
		}

		return current($this->csv);
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

	public function setTranslationTableAuto(): void
	{
		$sel = $this->getSelectedTable([]);
		$this->setTranslationTable($sel);
	}

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
		$this->csv = null;
		$this->translation = null;
		$this->skip = 1;

		if ($this->cache_key) {
			Static_Cache::remove($this->cache_key);
		}
	}

	public function loaded(): bool
	{
		return null !== $this->csv;
	}

	public function ready(): bool
	{
		return $this->loaded() && !empty($this->translation);
	}

	public function count(): ?int
	{
		return null !== $this->csv ? count($this->csv) : null;
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

		return reset($this->csv) ?: null;
	}

	public function hasRawHeaderColumn(string $label): bool
	{
		return in_array($label, $this->getRawHeader(), true);
	}

	public function orderBy(string $column): void
	{
		$col = array_search($column, $this->translation, true);

		if ($col === false) {
			throw new \InvalidArgumentException('Unknown column: ' . $column);
		}

		$header = array_slice($this->csv, 0, $this->skip);
		$rows = array_slice($this->csv, $this->skip);

		usort($rows, fn($a, $b) => strcmp($a[$col] ?? '', $b[$col] ?? ''));

		$rows = $header + $rows;

		// Renumber array
		$this->csv = array_combine(range(1, count($rows)), array_values($rows));
	}
}
