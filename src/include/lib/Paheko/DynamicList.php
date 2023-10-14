<?php

namespace Paheko;

use Paheko\DB;
use Paheko\Users\Session;

use KD2\DB\EntityManager as EM;

class DynamicList implements \Countable
{
	/**
	 * List of columns
	 * - The key is the column alias (AS ...)
	 * - Each column is an array
	 * - If the array is empty [] then a SELECT will be done on that table column,
	 * but it will not be included in HTML table
	 * - If the key 'select' exists, then it will be used as the SELECT clause
	 * - If the key 'label' exists, it will be used in the HTML table as its header
	 * (if not, the result will still be available in the loop, just it will not generate a column in the HTML table)
	 * - If the key 'export' is TRUE, then the column will ONLY be included in CSV/ODS/XLSX exports
	 * - If the key 'export' is FALSE, then the column will NOT be included in exports
	 * (if the key `export` is NULL, or not set, then the column will be included both in HTML and in exports)
	 */
	protected array $columns;

	/**
	 * List of tables (including joins)
	 */
	protected string $tables;

	/**
	 * WHERE clause
	 */
	protected string $conditions;

	/**
	 * GROUP BY clause
	 */
	protected ?string $group = null;

	/**
	 * Default order column (must reference a valid key of $columns)
	 */
	protected string $order;

	/**
	 * Modifier callback function
	 * This will be called for each row
	 */
	protected $modifier;

	/**
	 * Export modifier callback function
	 * Called for each row, for export only
	 */
	protected $export_callback;

	/**
	 * Table caption, used for the expor tfilename
	 */
	protected string $title = 'Liste';

	/**
	 * COUNT clause
	 */
	protected string $count = 'COUNT(*)';

	/**
	 * Tables used for the COUNT
	 * By default, $tables is used, but sometimes you don't need to JOIN
	 * that many tables just to do a COUNT.
	 */
	protected ?string $count_tables = null;

	/**
	 * @see setEntity
	 */
	protected ?string $entity = null;
	protected ?string $entity_select = null;

	/**
	 * Default ASC/DESC
	 */
	protected bool $desc = true;

	/**
	 * Number of items per page
	 * Set to NULL to disable LIMIT clause
	 */
	protected ?int $per_page = 100;

	/**
	 * Current page
	 */
	protected int $page = 1;

	/**
	 * Parameters to be binded to the SQL query
	 */
	protected array $parameters = [];

	/**
	 * Elements that should be used in the preference hash (stored in user preferences)
	 */
	protected array $preference_hash_elements = ['tables' => true, 'columns' => true, 'conditions' => true, 'group' => true];

	/**
	 * COUNT result, cached here to avoid multiple requests
	 */
	private ?int $count_result = null;

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

	public function togglePreferenceHashElement(string $name, bool $enable): void
	{
		$this->preference_hash_elements[$name] = $enable;
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

	/**
	 * If an entity is set, then each row will return the specified entity
	 * (using the SELECT clause passed) instead of the specified columns.
	 * Columns will only be used for the header and ordering
	 */
	public function setEntity(string $entity, string $select = '*')
	{
		$this->entity = $entity;
		$this->entity_select = $select;
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
			$sql = sprintf('SELECT %s FROM %s WHERE %s;', $this->count, $this->count_tables ?? $this->tables, $this->conditions);
			$this->count_result = DB::getInstance()->firstColumn($sql, $this->parameters);
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

	public function setCountTables(string $tables)
	{
		$this->count_tables = $tables;
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

			if (isset($properties['export'])) {
				if (!$properties['export'] && $export) {
					continue;
				}
				elseif ($properties['export'] && !$export) {
					continue;
				}
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
		if ($this->entity) {
			$list = EM::getInstance($this->entity)->iterate($this->SQL());
		}
		else {
			$list = DB::getInstance()->iterate($this->SQL(), $this->parameters);
		}

		foreach ($list as $row_key => $row) {
			if ($this->modifier) {
				call_user_func_array($this->modifier, [&$row]);
			}

			// Hide columns without a label in results
			if (!$this->entity) {
				foreach ($this->columns as $key => $config) {
					if (empty($config['label']) && !$include_hidden) {
						unset($row->$key);
					}
				}
			}

			yield $row_key => $row;
		}
	}

	public function SQL()
	{
		$start = ($this->page - 1) * $this->per_page;
		$db = DB::getInstance();

		if ($this->entity) {
			$select = $this->entity_select;
		}
		else {
			$columns = [];

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

			$select = implode(', ', $columns);
		}

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
			$select, $this->tables, $this->conditions, $group, $order);

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
			$elements = [];

			foreach ($this->preference_hash_elements as $e => $enabled) {
				if (!$enabled) {
					continue;
				}

				$elements[$e] = $this->$e;
			}

			ksort($elements);

			$hash = md5(json_encode($elements));
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
			&& ($order != ($preferences->o ?? null) || $desc != ($preferences->d ?? null))) {
			if ($order == $this->order && $desc == $this->desc) {
				$u->deletePreference('list_' . $hash);
			}
			else {
				$u->setPreference('list_' . $hash, ['o' => $order, 'd' => $desc]);
			}
		}

		if ($order) {
			$this->orderBy($order, $desc);
		}

		if ($page) {
			$this->page = (int) $page;
		}

		if ($this->per_page !== null && ($nb = Session::getPreference('page_size'))) {
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

		$url = Utils::getModifiedURL('?p=DDD');

		$out = '<ul class="pagination">';

		foreach ($pagination as $page) {
			$out .= sprintf('<li class="%s">', $page['class'] ?? '');

			if (!empty($use_buttons)) {
				$out .= sprintf('<button type="submit" name="_dl_page" value="%d">%s</button>', $page['id'], htmlspecialchars($page['label']));
			}
			else {
				$out .= sprintf('<a accesskey="%s" href="%s">%s</a>',
					$page['accesskey'] ?? '',
					str_replace('DDD', $page['id'], $url),
					htmlspecialchars($page['label'])
				);
			}

			$out .= "</li>\n";
		}

		$out .= '</ul>';
		return $out;
	}
}