<?php

namespace Garradin;

use KD2\Dumbyer;

class UserTemplate extends Dumbyer
{
	public function __construct()
	{
		$config = Config::getInstance();

		$this->assignArray([
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

		$this->assign('visitor_lang', $lang);

		$params = [
			'template' => $this,
		];

		Plugin::fireSignal('usertemplate.init', $params);

		$this->registerSection('pages', [$this, 'sectionPages']);
		$this->registerSection('articles', [$this, 'sectionArticles']);
		$this->registerSection('categories', [$this, 'sectionCategories']);

		$this->registerSection('files', [$this, 'sectionFiles']);
		$this->registerSection('documents', [$this, 'sectionDocuments']);
		$this->registerSection('images', [$this, 'sectionImages']);
	}

	public function fetch(string $file): string
	{
		$hash = sha1($path);
		$cpath = CACHE_ROOT . '/compiled/s_' . $hash . '.php';

		if (file_exists($cpath) && filemtime($cpath) >= filemtime($file)) {
			ob_start();
			include $cpath;
			return ob_get_clean();
		}

		try {
			$code = $this->compile(file_get_contents($file));
			ob_start();
			eval('?>' . $code);
			$return = ob_get_clean();
		}
		catch (\Exception $e) {
			throw new Dumbyer_Exception('Erreur de syntaxe : ' . $e->getMessage(), 0, $e);
		}

		if (!file_exists(dirname($cpath)))
		{
			Utils::safe_mkdir(dirname($cpath), 0777, true);
		}

		file_put_contents($cpath, $code);
		return $return;
	}

	protected function sectionCategories(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_CATEGORY;
		return $this->sectionPages($params);
	}

	protected function sectionArticles(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_PAGE;
		return $this->sectionPages($params);
	}

	protected function sectionPages(array $params): \Generator
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

		foreach ($this->sectionSQL($params) as $row) {
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

	protected function sectionImages(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND f.image = 1';
		return $this->sectionFiles($params);
	}

	protected function sectionDocuments(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND f.image = 0';
		return $this->sectionFiles($params);
	}

	protected function sectionFiles(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['select'] = 'f.*';
		$params['tables'] = 'files f';
		$params['where'] .= ' AND f.public = 1';

		if (isset($params['except_in_text'])) {
			$found = Page::findTaggedAttachments($params['except_in_text']);
			$found = array_map('intval', $found);
			$params['where'] .= sprintf(' AND f.id NOT IN (%s)', implode(', ', $found));
		}

		foreach ($this->sectionSQL($params) as $row) {
			$file = new File;
			$file->load($row);
			$row = $file->asArray();
			$row['url'] = $page->url();

			yield $row;
		}
	}

	protected function sectionSQL(array $params): \Generator
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
