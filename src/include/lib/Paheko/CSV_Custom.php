<?php

namespace Paheko;

use KD2\UserSession;

class CSV_Custom
{
	protected ?UserSession $session;
	protected ?string $key;
	protected ?array $csv;
	protected ?array $translation = null;
	protected array $columns;
	protected array $columns_defaults;
	protected array $mandatory_columns = [];
	protected int $skip = 1;
	protected $modifier = null;
	protected array $_default;

	public function __construct(?UserSession $session = null, ?string $key = null)
	{
		$this->session = $session;
		$this->key = $key;
		$this->csv = $this->session ? $this->session->get($this->key) : null;
		$this->translation = $this->session ? $this->session->get($this->key . '_translation') : null;
		$this->skip = $this->session ? $this->session->get($this->key . '_skip') ?? 1 : 1;
	}

	public function load(?array $file): void
	{
		if (empty($file['size']) || empty($file['tmp_name']) || empty($file['name'])) {
			throw new UserException('Fichier invalide');
		}

		$path = $file['tmp_name'];

		$this->loadFile($path);

		@unlink($path);
	}

	public function loadFile(string $path): void
	{
		if (CALC_CONVERT_COMMAND && strtolower(substr($path, -4)) != '.csv') {
			$path = CSV::convertUploadIfRequired($path, true);
		}

		$this->csv = CSV::readAsArray($path);

		if (!count($this->csv)) {
			throw new UserException('Ce fichier est vide (aucune ligne trouvée).');
		}

		if ($this->session) {
			$this->session->set($this->key, $this->csv);
			$this->session->save();
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
			$this->_default = array_map(function ($a) { return null; }, array_flip($this->translation));
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

	public function setIndexedTable(array $table): void
	{
		if (!count($table)) {
			throw new UserException('Aucune colonne n\'a été sélectionnée');
		}

		foreach ($this->mandatory_columns as $key) {
			if (!in_array($key, $table, true)) {
				throw new UserException(sprintf('La colonne "%s" est obligatoire mais n\'a pas été sélectionnée ou n\'existe pas.', $this->columns[$key]));
			}
		}

		$this->translation = $table;

		if ($this->session) {
			$this->session->set($this->key . '_translation', $this->translation);
			$this->session->save();
		}
	}

	public function clear(): void
	{
		if ($this->session) {
			$this->session->set($this->key, null);
			$this->session->set($this->key . '_translation', null);
			$this->session->set($this->key . '_skip', null);
			$this->session->save();
		}

		$this->csv = null;
		$this->translation = null;
		$this->skip = 1;
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

		if ($this->session) {
			$this->session->set($this->key . '_skip', $count);
			$this->session->save();
		}
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

		return implode(', ', array_intersect_key($c, array_flip($this->getMandatoryColumns())));
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
}