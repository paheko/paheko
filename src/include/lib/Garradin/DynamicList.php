<?php

namespace Garradin;

class DynamicList
{
	protected $columns;
	protected $tables;
	protected $conditions;
	protected $group;
	protected $order;
	protected $modifier;
	protected $title = 'Liste';
	protected $count = 'COUNT(*)';
	protected $desc = true;
	protected $per_page = 100;
	protected $page = 1;

	private $count_result;

	public function __construct(array $columns, string $tables, string $conditions)
	{
		$this->columns = $columns;
		$this->tables = $tables;
		$this->conditions = $conditions;
		$this->order = key($columns);
	}

	public function __get($key)
	{
		return $this->$key;
	}

	public function setTitle(string $title) {
		$this->title = $title;
	}

	public function setModifier(callable $fn) {
		$this->modifier = $fn;
	}

	public function setPageSize(?int $size) {
		$this->per_page = $size;
	}

	public function setConditions(string $conditions)
	{
		$this->conditions = $conditions;
	}

	public function orderBy(string $key, bool $desc)
	{
		if (!array_key_exists($key, $this->columns)) {
			throw new UserException('Invalid order: ' . $key);
		}

		$this->order = $key;
		$this->desc = $desc;
	}

	public function groupBy(string $value)
	{
		$this->group = $value;
	}

	public function count()
	{
		if (null === $this->count_result) {
			$sql = sprintf('SELECT %s FROM %s WHERE %s;', $this->count, $this->tables, $this->conditions);
			$this->count_result = DB::getInstance()->firstColumn($sql);
		}

		return $this->count_result;
	}

	public function export(string $name, string $format = 'csv')
	{
		$columns = [];

		foreach ($this->columns as $key => $column) {
			if (empty($column['label'])) {
				$columns[] = $key;
				continue;
			}

			$columns[] = $column['label'];
		}

		if ('csv' == $format) {
			CSV::toCSV($name, $this->iterate(false), $columns);
		}
		else if ('ods' == $format) {
			CSV::toODS($name, $this->iterate(false), $columns);
		}
		else {
			throw new UserException('Invalid export format');
		}
	}

	public function paginationURL()
	{
		$query = array_merge($_GET, ['p' => '[ID]']);
		$url = Utils::getSelfURL($query);
		return $url;
	}

	public function orderURL(string $order, bool $desc)
	{
		$query = array_merge($_GET, ['o' => $order, 'd' => (int) $desc]);
		$url = Utils::getSelfURL($query);
		return $url;
	}

	public function setCount(string $count)
	{
		$this->count = $count;
	}

	public function iterate(bool $paginate = true)
	{
		$start = ($this->page - 1) * $this->per_page;
		$columns = [];

		foreach ($this->columns as $alias => $properties) {
			$select = array_key_exists('select', $properties) ? $properties['select'] : $alias;

			if (null === $select) {
				$select = 'NULL';
			}

			$columns[] = sprintf('%s AS %s', $select, $alias);
		}

		$columns = implode(', ', $columns);

		if (isset($this->columns[$this->order]['order'])) {
			$order = sprintf($this->columns[$this->order]['order'], $this->desc ? 'DESC' : 'ASC');
		}
		else {
			$order = $this->order;

			if (true === $this->desc) {
				$order .= ' DESC';
			}
		}

		$group = $this->group ? 'GROUP BY ' . $this->group : '';

		$sql = sprintf('SELECT %s FROM %s WHERE %s %s ORDER BY %s',
			$columns, $this->tables, $this->conditions, $group, $order);

		if ($paginate && null !== $this->per_page) {
			$sql .= sprintf(' LIMIT %d,%d', $start, $this->per_page);
		}

		foreach (DB::getInstance()->iterate($sql) as $row) {
			if ($this->modifier) {
				$row = call_user_func($this->modifier, $row);
			}

			yield $row;
		}
	}

	public function loadFromQueryString()
	{
		if (!empty($_GET['export'])) {
			$this->export($this->title, $_GET['export']);
			exit;
		}

		if (!empty($_GET['o'])) {
			$this->orderBy($_GET['o'], !empty($_GET['d']));
		}

		if (!empty($_GET['p'])) {
			$this->page = (int)$_GET['p'];
		}
	}
}