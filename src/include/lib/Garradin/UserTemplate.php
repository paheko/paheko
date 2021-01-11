<?php

namespace Garradin;

use KD2\Dumbyer;
use KD2\Dumbyer_Exception;

use Garradin\Files\Files;
use Garradin\Files\Folders;

class UserTemplate extends Dumbyer
{
	protected $path;
	protected $hash;
	protected $modified;
	protected $file;

	public function __construct(?File $file = null)
	{
		if ($file) {
			$this->file = $file;
			$this->hash = $file->hash;
			$this->modified = $file->modified;
		}

		$config = Config::getInstance();

		$this->assignArray([
			'config'    => $config->asArray(),
			'root_url'  => WWW_URL,
			'admin_url' => ADMIN_URL,
			'_GET'      => $_GET,
			'_POST'     => $_POST,
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

		$this->registerSection('pages', [self::class, 'sectionPages']);
		$this->registerSection('articles', [self::class, 'sectionArticles']);
		$this->registerSection('categories', [self::class, 'sectionCategories']);

		$this->registerSection('files', [self::class, 'sectionFiles']);
		$this->registerSection('documents', [self::class, 'sectionDocuments']);
		$this->registerSection('images', [self::class, 'sectionImages']);

		$this->registerFunction('http', [self::class, 'functionHTTP']);
		$this->registerFunction('include', [self::class, 'functionInclude']);
	}

	public function setSource(string $path)
	{
		$this->path = $path;
		$this->hash = sha1($path);
		$this->modified = filemtime($path);
	}

	public function display(): void
	{
		$cpath = CACHE_ROOT . '/compiled/s_' . $this->hash . '.php';

		if (file_exists($cpath) && filemtime($cpath) >= $this->modified) {
			include $cpath;
			return;
		}

		try {
			$code = $this->compile($this->file ? $this->file->fetch() : file_get_contents($this->path));
			eval('?>' . $code);
		}
		catch (\Exception $e) {
			throw new Dumbyer_Exception('Erreur de syntaxe : ' . $e->getMessage(), 0, $e);
		}

		if (!file_exists(dirname($cpath))) {
			Utils::safe_mkdir(dirname($cpath), 0777, true);
		}

		file_put_contents($cpath, $code);
	}

	public function fetch(File $file): string
	{
		ob_start();
		$this->display($file);
		return ob_get_clean();
	}

	static public function functionInclude(array $params, UserTemplate $ut): string
	{
		if (empty($params['file'])) {
			throw new Dumbyer_Exception('Argument "file" manquant pour la fonction "include"');
		}

		$file = Files::getSystemFile($params['file'], Folders::TEMPLATES);

		if (!$file) {
			throw new Dumbyer_Exception(sprintf('Le fichier à inclure "%s" n\'existe pas', $params['file']));
		}
		return $ut->fetch($file);
	}

	static public function functionHTTP(array $params): void
	{
		if (headers_sent()) {
			return;
		}

		if (isset($params['code'])) {
			static $codes = [
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => 'Switch Proxy',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				418 => 'I\'m a teapot',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				425 => 'Unordered Collection',
				426 => 'Upgrade Required',
				449 => 'Retry With',
				450 => 'Blocked by Windows Parental Controls',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				509 => 'Bandwidth Limit Exceeded',
				510 => 'Not Extended',
			];

			if (!isset($codes[$params['code']])) {
				throw new Dumbyer_Exception('Code HTTP inconnu');
			}

			header(sprintf('HTTP/1.1 %d %s', $params['code'], $codes[$params['code']]), true);
		}
		elseif (isset($params['redirect'])) {
			header('Location: ' . WWW_URL . $params['redirect'], true);
		}
		elseif (isset($params['type'])) {
			header('Content-Type: ' . $params['type'], true);
		}
		else {
			throw new Dumbyer_Exception('No valid parameter found for http function');
		}
	}

	static public function sectionCategories(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_CATEGORY;
		return $this->sectionPages($params);
	}

	static public function sectionArticles(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND w.type = ' . Page::TYPE_PAGE;
		return $this->sectionPages($params);
	}

	static public function sectionPages(array $params): \Generator
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

	static public function sectionImages(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND f.image = 1';
		return $this->sectionFiles($params);
	}

	static public function sectionDocuments(array $params): \Generator
	{
		if (!array_key_exists('where', $params)) {
			$params['where'] = '';
		}

		$params['where'] .= ' AND f.image = 0';
		return $this->sectionFiles($params);
	}

	static public function sectionFiles(array $params): \Generator
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

	static public function sectionSQL(array $params): \Generator
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
