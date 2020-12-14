<?php

namespace Garradin;

use KD2\UserSession;

class CSV_Custom
{
	protected $session;
	protected $key;
	protected $csv;
	protected $translation;
	protected $columns;
	protected $mandatory_columns = [];
	protected $skip = 1;

	public function __construct(UserSession $session, string $key)
	{
		$this->session = $session;
		$this->key = $key;
		$this->csv = $this->session->get($this->key);
		$this->translation = $this->session->get($this->key . '_translation') ?: [];
		$this->skip = $this->session->get($this->key . '_skip') ?: 1;
	}

	public function load(array $file)
	{
		if (empty($file['size']) || empty($file['tmp_name'])) {
			throw new UserException('Fichier invalide');
		}

		$csv = CSV::readAsArray($file['tmp_name']);

		if (!count($csv)) {
			throw new UserException('Ce fichier est vide (aucune ligne trouvée).');
		}

		$this->session->set($this->key, $csv);
	}

	public function iterate(): \Generator
	{
		if (empty($this->csv)) {
			throw new \LogicException('No file has been loaded');
		}

		if (!$this->columns || !$this->translation) {
			throw new \LogicException('Missing columns or translation table');
		}

		$default = array_map(function ($a) { return null; }, $this->columns);

		$i = 0;

		foreach ($this->csv as $k => $line) {
			if ($i++ < $this->skip) {
				continue;
			}

			$row = $default;

			foreach ($line as $col => $value) {
				if (!isset($this->translation[$col])) {
					continue;
				}

				$row[$this->translation[$col]] = $value;
			}

			$row = (object) $row;
			yield $k => $row;
		}
	}

	public function getFirstLine(): array
	{
		if (empty($this->csv)) {
			throw new \LogicException('No file has been loaded');
		}

		return reset($this->csv);
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

		foreach ($selected as $k => &$v) {
			if (isset($source[$k])) {
				$v = $source[$k];
			}
			elseif (isset($this->translation[$k])) {
				$v = $this->translation[$k];
			}
			elseif (false !== ($pos = array_search($v, $this->columns, true))) {
				$v = $pos;
			}
			else {
				$v = null;
			}
		}

		return $selected;
	}

	public function getTranslationTable(): array
	{
		return $this->translation;
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
				throw new UserException('Colonne inconnue');
			}

			$translation[(int)$csv] = $target;
		}

		foreach ($this->mandatory_columns as $key) {
			if (!in_array($key, $translation, true)) {
				throw new UserException(sprintf('La colonne "%s" est obligatoire mais n\'a pas été sélectionnée ou n\'existe pas.', $this->columns[$key]));
			}
		}

		$this->translation = $translation;

		$this->session->set($this->key . '_translation', $this->translation);
	}

	public function clear(): void
	{
		$this->session->set($this->key, null);
		$this->session->set($this->key . '_translation', null);
		$this->session->set($this->key . '_skip', null);
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
		$this->session->set($this->key . '_skip', $count);
	}

	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}

	public function setMandatoryColumns(array $columns): void
	{
		$this->mandatory_columns = $columns;
	}

	public function getColumnsString(): string
	{
		return implode(', ', $this->getColumns());
	}

	public function getMandatoryColumnsString(): string
	{
		return implode(', ', $this->getMandatoryColumns());
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function getMandatoryColumns(): array
	{
		return array_intersect_key($this->columns, $this->mandatory_columns);
	}
}