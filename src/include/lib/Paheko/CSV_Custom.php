<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Files\Conversion;

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
	protected array $_default;
	protected ?string $cache_key = null;

	public function __construct(?Session $session = null, ?string $key = null)
	{
		$this->session = $session;
		$this->key = $key;
		$id = $session ? $session::getUserId() : null;
		$id ??= 'anon';
		$this->cache_key = $id . '_' . $key;

		if ($this->cache_key && !Static_Cache::hasExpired($this->cache_key)) {
			$data = Static_Cache::import($this->cache_key);

			$this->csv = $data['csv'];
			$this->translation = $data['translation'];
			$this->skip = $data['skip'];
		}
	}

	public function __destruct()
	{
		if ($this->session && $this->cache_key && ($this->csv || $this->translation || $this->skip !== 1)) {
			Static_Cache::export($this->cache_key,
				['csv' => $this->csv, 'translation' => $this->translation, 'skip' => $this->skip],
				new \DateTime('+3 hours')
			);

			Static_Cache::prune();
		}
	}

	public function load(?array $file): void
	{
		if (empty($file['size']) || empty($file['tmp_name']) || empty($file['name'])) {
			throw new UserException('Fichier invalide, ou aucun fichier fourni');
		}

		$path = $file['tmp_name'];

		$this->loadFile($path);

		@unlink($path);
	}

	public function canConvert(): bool
	{
		return Conversion::canConvertToCSV();
	}

	public function loadFile(string $path): void
	{
		// Automatically convert
		if (strtolower(substr($path, -4)) !== '.csv' && Conversion::canConvertToCSV()) {
			$path = Conversion::toCSVAuto($path);
		}

		if (!$path) {
			throw new UserException('Ce fichier n\'est pas dans un format accepté.');
		}

		$this->csv = CSV::readAsArray($path);

		if (!count($this->csv)) {
			throw new UserException('Ce fichier est vide (aucune ligne trouvée).');
		}
	}

	public function iterate(): \Generator
	{
		if (empty($this->csv)) {
			throw new \LogicException('No file has been loaded');
		}

		if (!$this->columns || !$this->translation) {
			throw new \LogicException('Missing columns or translation table');
		}

		for ($i = 0; $i < count($this->csv); $i++) {
			if ($i < $this->skip) {
				continue;
			}

			yield $i+1 => $this->getLine($i + 1);
		}
	}

	public function getLine(int $i): ?\stdClass
	{
		if (!isset($this->csv[$i])) {
			return null;
		}

		if (!isset($this->_default)) {
			$this->_default = array_fill_keys(array_flip($this->translation), null);
		}

		$row = $this->_default;

		foreach ($this->csv[$i] as $col => $value) {
			if (!isset($this->translation[$col])) {
				continue;
			}

			$row[$this->translation[$col]] = trim($value);
		}

		$row = (object) $row;

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

		foreach ($this->mandatory_columns as $column) {
			// Either one of these columns is mandatory
			if (is_array($column)) {
				$found = false;
				$names = [];

				foreach ($column as $c) {
					if (in_array($c, $table, true)) {
						$found = true;
						break;
					}
					else {
						$names[] = $this->columns[$c];
					}
				}

				if (!$found) {
					$names = array_map(fn($a) => '"' . $a . '"', $column);
					throw new UserException(sprintf('Une des colonnes (%s) est obligatoire, mais aucune n\'a été sélectionnée ou n\'existe.', implode(', ', $names)));
				}
			}
			else {
				if (!in_array($column, $table, true)) {
					throw new UserException(sprintf('La colonne "%s" est obligatoire mais n\'a pas été sélectionnée ou n\'existe pas.', $this->columns[$column]));
				}
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

	public function getMandatoryColumnsString(): string
	{
	if (!empty($this->columns_defaults)) {
			$c = array_intersect_key($this->columns_defaults, $this->columns);
		}
		else {
			$c = $this->columns;
		}

		$names = [];

		foreach ($this->getMandatoryColumns() as $column) {
			if (is_array($column)) {
				$list = [];

				foreach ($column as $column2) {
					$list[] = $c[$column2];
				}

				$names[] = implode(' ou ', $list);
				unset($list, $column2);
			}
			else {
				$names[] = $c[$column];
			}
		}

		return implode(', ', $names);
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
			'rows'              => $this->ready() ? $this->iterate() : null,
			'header'            => $this->getHeader(),
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

	public function hasRawHeaderColumn(string $label)
	{
		return in_array($label, $this->getRawHeader(), true);
	}
}
