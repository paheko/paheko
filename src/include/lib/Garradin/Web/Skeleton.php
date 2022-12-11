<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Entities\Web\Page;
use Garradin\UserException;
use Garradin\UserTemplate\UserForms;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Config;
use Garradin\Plugin;
use Garradin\Utils;

use KD2\Brindille_Exception;
use KD2\DB\EntityManager as EM;

use const Garradin\{ROOT, ADMIN_URL};

class Skeleton
{
	const TEMPLATE_TYPES = '!^(?:text/(?:html|plain)|\w+/(?:\w+\+)?xml)$!';

	protected ?string $path;
	protected ?File $file = null;

	static public function route(string $uri): void
	{
		$page = null;

		if (substr($uri, 0, 5) == 'form/') {
			$uri = substr($uri, 5);
			$path = 'forms/' . $uri;
		}
		else {
			if (Config::getInstance()->site_disabled && $uri != 'content.css') {
				Utils::redirect(ADMIN_URL);
			}

			if ($uri == '') {
				$path = 'web/index.html';
			}
			elseif (($page = Web::getByURI($uri)) && $page->status == Page::STATUS_ONLINE) {
				$path = 'web/' . $page->template();
				$page = $page->asTemplateArray();
			}
			// No page with this URI, then we expect this might be a skeleton path
			else {
				$path = 'web/' . $uri;
			}
		}

		if (substr($path, -1) == '/') {
			$path .= 'index.html';
		}

		try {
			$s = new self($path);
			$s->serve($uri, compact('page'));
		}
		catch (\InvalidArgumentException $e) {
			if (file_exists(UserTemplate::DIST_ROOT . 'web/404.html')) {
				header('Content-Type: text/html;charset=utf-8', true);
				header('HTTP/1.1 404 Not Found', true);
				$path = 'web/404.html';

				$s = new self($path);
				$s->serve($uri);
			}
			else {
				throw new UserException('Cette page n\'existe pas');
			}
		}
	}

	public function __construct(string $path)
	{
		if (!self::isValidPath($path)) {
			throw new \InvalidArgumentException('This skeleton path is invalid');
		}

		if (!Files::exists(File::CONTEXT_SKELETON . '/' . $path) && !file_exists(UserTemplate::DIST_ROOT . $path)) {
			throw new \InvalidArgumentException('This skeleton does not exist');
		}

		$this->path = $path;
	}

	static public function isValidPath(string $path)
	{
		return (bool) preg_match('!^(?:web|forms/[\w\d_-]+)/[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $path);
	}

	public function defaultPath(): ?string
	{
		$path = UserTemplate::DIST_ROOT . $this->path;

		if (file_exists($path)) {
			return $path;
		}

		return null;
	}

	public function serve(string $uri, array $params = []): void
	{
		if (Plugin::fireSignal('http.request.skeleton.before', $params)) {
			return;
		}

		$type = $this->type();

		if (!$type) {
			throw new \InvalidArgumentException('Invalid skeleton type');
		}

		// Serve a template
		if (preg_match(self::TEMPLATE_TYPES, $type)) {
			if (substr($this->path, 0, 6) == 'forms/') {
				$name = substr($uri, 0, strrpos($uri, '/'));
				$file = substr($uri, strrpos($uri, '/') + 1) ?: 'index.html';
				$form = UserForms::get($name);

				if (!$form || !$form->enabled) {
					http_response_code(404);
					throw new UserException('Ce formulaire n\'existe pas ou n\'est pas activé.');
				}

				$path = 'forms/' . $name . '/' . $file;
				$form->template($file)->serve();
			}
			else {
				$ut = new UserTemplate($this->path);
				$ut->assignArray($params);
				$ut->serve($uri);
			}
		}
		// Serve a static file
		elseif ($file = $this->file()) {
			$file->serve();
		}
		// Serve a static skeleton file (from skel-dist)
		else {
			Cache::link($uri, $this->defaultPath());
			header(sprintf('Content-Type: %s;charset=utf-8', $type), true);
			readfile($this->defaultPath());
			flush();
		}

		Plugin::fireSignal('http.request.skeleton.after', $params);
	}

	public function file(): ?File
	{
		return Files::get(File::CONTEXT_SKELETON . '/' . $this->path);
	}

	public function reset()
	{
		if ($file = $this->file()) {
			$file->delete();
		}
	}

	static public function resetSelected(array $selected)
	{
		foreach ($selected as $file) {
			$f = new self($file);
			$f->reset();
		}
	}

	static public function list(): array
	{
		$sources = [];

		$path = ROOT . '/skel-dist/web';
		$i = new \DirectoryIterator($path);

		foreach ($i as $file) {
			if ($file->isDot() || $file->isDir()) {
				continue;
			}

			$mime = mime_content_type($file->getRealPath());

			$sources[$file->getFilename()] = ['is_text' => substr($mime, 0, 5) == 'text/', 'changed' => null];
		}

		unset($i);

		$list = Files::list(File::CONTEXT_SKELETON . '/web');

		foreach ($list as $file) {
			if ($file->type != $file::TYPE_FILE) {
				continue;
			}

			$sources[$file->name] = ['is_text' => substr($file->mime, 0, 5) == 'text/', 'changed' => $file->modified];
		}

		ksort($sources);

		return $sources;
	}

	public function type(): ?string
	{
		$name = $this->file->name ?? $this->path;
		$dot = strrpos($name, '.');

		// Templates with no extension are returned as HTML by default
		// unless {{:http type=...}} is used
		if ($dot === false) {
			return 'text/html';
		}

		$ext = substr($name, $dot+1);

		// Common types
		switch ($ext) {
			case 'txt':
				return 'text/plain';
			case 'html':
			case 'htm':
			case 'tpl':
			case 'btpl':
			case 'skel':
				return 'text/html';
			case 'xml':
				return 'text/xml';
			case 'css':
				return 'text/css';
			case 'js':
				return 'text/javascript';
			case 'png':
			case 'gif':
			case 'webp':
				return 'image/' . $ext;
			case 'jpeg':
			case 'jpg':
				return 'image/jpeg';
		}

		if (preg_match('/php\d*/i', $ext)) {
			return null;
		}

		if ($this->file) {
			return $this->file->mime;
  		}

		return null;
	}
}
