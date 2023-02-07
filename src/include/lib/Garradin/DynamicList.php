<?php

namespace Garradin;

use Garradin\Users\Session;

class DynamicList implements \Countable
{
	protected $columns;
	protected $tables;
	protected $conditions;
	protected $group;
	protected $order;
	protected $modifier;
	protected $export_callback;
	protected $title = 'Liste';
	protected $count = 'COUNT(*)';
	protected $desc = true;
	protected $per_page = 100;
	protected $page = 1;
	protected array $parameters = [];

	private $count_result;

	public function __construct(array $columns, string $tables, string $conditions = '1')
	{
		$this->columns = $columns;
		$this->tables = $tables;
		$this->conditions = $conditions;
		$this->order = key($columns);
	}

	public function __isset($key)
	{
		return property_exists($this, $key);
	}

	public function __get($key)
	{
		return $this->$key;
	}

	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	public function setTitle(string $title) {
		$this->title = $title;
	}

	public function setModifier(callable $fn) {
		$this->modifier = $fn;
	}

	public function setExportCallback(callable $fn) {
		$this->export_callback = $fn;
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

	public function count(): int
	{
		if (null === $this->count_result) {
			$sql = sprintf('SELECT %s FROM %s WHERE %s;', $this->count, $this->tables, $this->conditions);
			$this->count_result = DB::getInstance()->firstColumn($sql);
		}

		return (int) $this->count_result;
	}

	public function export(string $name, string $format = 'csv')
	{
		$this->setPageSize(null);
		$columns = [];

		foreach ($this->columns as $key => $column) {
			if (empty($column['label'])) {
				$columns[] = $key;
				continue;
			}

			$columns[] = $column['label'];
		}

		CSV::export($format, $name, $this->iterate(false), $this->getExportHeaderColumns(), $this->export_callback);
	}

	public function asArray(): array
	{
		$out = [];

		foreach ($this->iterate(true) as $row) {
			$out[] = $row;
		}

		return $out;
	}

	public function orderURL(string $order, bool $desc)
	{
		$query = array_merge($_GET, ['o' => $order, 'd' => (int) $desc]);
		$url = Utils::getSelfURI($query);
		return $url;
	}

	public function setCount(string $count)
	{
		$this->count = $count;
	}

	public function getHeaderColumns(bool $export = false)
	{
		$columns = [];

		foreach ($this->columns as $alias => $properties) {
			if (isset($properties['only_with_order']) && !($properties['only_with_order'] == $this->order)) {
				continue;
			}

			// Skip columns that require a certain order AND paginated result
			if (isset($properties['only_with_order']) && $this->page > 1) {
				continue;
			}

			if (!isset($properties['label'])) {
				continue;
			}

			if (!$export && !empty($properties['export_only'])) {
				continue;
			}

			$columns[$alias] = $export ? $properties['label'] : $properties;
		}

		return $columns;
	}

	public function countHeaderColumns(): int
	{
		return count($this->getHeaderColumns());
	}

	public function getExportHeaderColumns(): array
	{
		return $this->getHeaderColumns(true);
	}

	public function iterate(bool $include_hidden = true)
	{
		foreach (DB::getInstance()->iterate($this->SQL(), $this->parameters) as $row) {
			if ($this->modifier) {
				call_user_func_array($this->modifier, [&$row]);
			}

			foreach ($this->columns as $key => $config) {
				if (empty($config['label']) && !$include_hidden) {
					unset($row->$key);
				}
			}

			yield $row;
		}
	}

	public function SQL()
	{
		$start = ($this->page - 1) * $this->per_page;
		$columns = [];
		$db = DB::getInstance();

		foreach ($this->columns as $alias => $properties) {
			// Skip columns that require a certain order (eg. calculating a running sum)
			if (isset($properties['only_with_order']) && !($properties['only_with_order'] == $this->order)) {
				continue;
			}

			// Skip columns that require a certain order AND paginated result
			if (isset($properties['only_with_order']) && $this->page > 1) {
				continue;
			}

			if (array_key_exists('select', $properties)) {
				$select = $properties['select'] ?? 'NULL';
				$columns[] = sprintf('%s AS %s', $select, $db->quoteIdentifier($alias));
			}
			else {
				$columns[] = $db->quoteIdentifier($alias);
			}
		}

		$columns = implode(', ', $columns);

		if (isset($this->columns[$this->order]['order'])) {
			$order = sprintf($this->columns[$this->order]['order'], $this->desc ? 'DESC' : 'ASC');
		}
		else {
			$order = $db->quoteIdentifiers($this->order);

			if (true === $this->desc) {
				$order .= ' DESC';
			}
		}

		$group = $this->group ? 'GROUP BY ' . $this->group : '';

		$sql = sprintf('SELECT %s FROM %s WHERE %s %s ORDER BY %s',
			$columns, $this->tables, $this->conditions, $group, $order);

		if (null !== $this->per_page) {
			$sql .= sprintf(' LIMIT %d,%d', $start, $this->per_page);
		}

		return $sql;
	}

	public function loadFromQueryString(): void
	{
		$export = $_POST['_dl_export'] ?? ($_GET['export'] ?? null);
		$page = $_POST['_dl_page'] ?? ($_GET['p'] ?? null);

		$order = null;
		$desc = null;
		$hash = null;
		$preferences = null;
		$u = null;

		if ($u = Session::getLoggedUser()) {
			$hash = md5(json_encode([$this->tables, $this->conditions, $this->columns, $this->group]));
			$preferences = $u->getPreference('list_' . $hash) ?? null;

			$order = $preferences->o ?? null;
			$desc = $preferences->d ?? null;
		}

		if (!empty($_POST['_dl_order'])) {
			$order = substr($_POST['_dl_order'], 1);
			$desc = substr($_POST['_dl_order'], 0, 1) == '>' ? true : false;
		}
		elseif (!empty($_GET['o'])) {
			$order = $_GET['o'];
			$desc = !empty($_GET['d']);
		}

		if ($export) {
			$this->export($this->title, $export);
			exit;
		}

		// Save current order, if different than default
		if ($u && $hash
			&& (($order != ($preferences->o ?? null) && $order != $this->order)
				|| ($desc != ($preferences->d ?? null) && $desc != $this->desc))) {
			$u->setPreference('list_' . $hash, ['o' => $order, 'd' => $desc]);
		}

		if ($order) {
			$this->orderBy($order, $desc);
		}

		if ($page) {
			$this->page = (int) $page;
		}

		if ($nb = Session::getPreference('page_size')) {
			$this->setPageSize((int) $nb);
		}
	}

	public function isPaginated(): bool
	{
		if (null === $this->per_page) {
			return false;
		}

		return $this->count() > $this->per_page;
	}

	public function getHTMLPagination(bool $use_buttons = false): string
	{
		if (!$this->isPaginated()) {
			return '';
		}

		$pagination = Utils::getGenericPagination($this->page, $this->count(), $this->per_page);

		if (empty($pagination)) {
			return '';
		}

		$url = Utils::getModifiedURL('?p=%d');

		$out = '<ul class="pagination">';

		foreach ($pagination as $page) {
			$out .= sprintf('<li class="%s">', $page['class'] ?? '');

			if (!empty($use_buttons)) {
				$out .= sprintf('<button type="submit" name="_dl_page" value="%d">%s</button>', $page['id'], htmlspecialchars($page['label']));
			}
			else {
				$out .= sprintf('<a accesskey="%s" href="%s">%s</a>',
					$page['accesskey'] ?? '',
					str_replace('%d', $page['id'], $url),
					htmlspecialchars($page['label'])
				);
			}

			$out .= "</li>\n";
		}

		$out .= '</ul>';
		return $out;
	}
}