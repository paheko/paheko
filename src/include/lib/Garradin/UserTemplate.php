<?php

namespace Garradin;

class UserTemplate
{
	protected $file;

	public function __construct(string $file)
	{
		$this->file = $file;
	}

	protected function fetch()
	{
		$config = Config::getInstance();

		$d = new Dumbyer;
		$d->assignArray([
			'nom_asso' => $config->get('nom_asso'),
			'adresse_asso' => $config->get('adresse_asso'),
			'email_asso' => $config->get('email_asso'),
			'site_asso' => $config->get('site_asso'),
			'root_url' => WWW_URL,
			'admin_url' => ADMIN_URL,
		]);

		$url = file_exists(DATA_ROOT . '/www/squelettes/default.css')
			? WWW_URL . 'squelettes/default.css'
			: WWW_URL . 'squelettes-dist/default.css';

		$this->assign('url_css_defaut', $url);

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			if (function_exists('locale_accept_from_http'))
			{
			   $lang = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
			}
			else
			{
				$lang = preg_replace('/[^a-z]/i', '', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				$lang = strtolower(substr($lang, 0, 2));
			}

			$lang = strtolower(substr($lang, 0, 2));
		}
		else
		{
			$lang = '';
		}

		$d->assign('visitor_lang', $lang);

		$params = [
			'dumbyer' => $d,
			'template' => $this,
		];

		Plugin::fireSignal('usertemplate.init', $params, $callback_return);

		$d->registerSection('pages', [$this, 'sectionPages']);
		$d->registerSection('articles', [$this, 'sectionArticles']);
		$d->registerSection('categories', [$this, 'sectionCategories']);
	}

	protected function sectionCategories(array $params, Dumbyer $tpl): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_CATEGORY;
		return $this->sectionPages($params, $tpl);
	}

	protected function sectionArticles(array $params, Dumbyer $tpl): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_PAGE;
		return $this->sectionPages($params, $tpl);
	}

	protected function sectionPages(array $params, Dumbyer $tpl): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['select'] = 'w.*';
		$params['tables'] = 'web_pages w INNER JOIN files f USING (id)';

		if (isset($params['search'])) {
			$params['tables'] .= ' INNER JOIN files_search s USING (id)';
			$params['select'] .= ', rank(matchinfo(s), 0, 1.0, 1.0) AS points';
			$params['where'] .= ' AND s MATCH :search';

			if (!isset($params['order'])) {
				$params['order'] = 'points DESC';
			}

			$params[':search'] = $params['search'];
			unset($params['search']);
		}

		foreach ($this->sectionSQL($params, $tpl) as $row) {
			$data = $row;
			unset($data['points']);

			$page = new Page;
			$page->load($data);
			$row = array_merge($row, $page->asArray());
			$row['url'] = $page->url();
			$row['raw'] = $page->raw();
			$row['html'] = $page->render();

			yield $row;
		}
	}

	protected function sectionSQL(array $params, Dumbyer $tpl): \Generator
	{
		static $defaults = [
			'select' => '*',
			'order' => 'rowid',
			'begin' => 0,
			'limit' => 1000,
			'where' => '',
		];

		if (!isset($params['tables'])) {
			throw new Dumbyer_Exception('Missing parameter "tables"');
		}

		foreach ($defaults as $key => $default_value) {
			if (!isset($params[$key])) {
				$params[$key] = $default_value;
			}
		}

		$sql = sprintf('SELECT %s FROM %s WHERE 1 %s %s ORDER BY %s LIMIT %d,%d;',
			$params['select'], $params['tables'], $params['where'] ?? '', $params['group'] ? 'GROUP BY ' . $params['group'] : '', $params['order'], $params['begin'], $params['limit']);

		$args = [];

		foreach ($params as $key => $value) {
			if (substr($key, 0, 1) == ':') {
				$args[substr($key, 1)] = $value;
			}
		}

		// FIXME: use protectSelect
		return DB::getInstance()->iterate($sql, $args);
	}
}
