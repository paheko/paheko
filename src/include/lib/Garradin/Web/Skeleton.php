<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\UserException;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Plugin;

use KD2\Brindille_Exception;
use KD2\DB\EntityManager as EM;

use const Garradin\ROOT;

class Skeleton
{
	const TEMPLATE_TYPES = '!^(?:text/(?:html|plain)|\w+/(?:\w+\+)?xml)$!';

	protected $path;

	static public function isValidPath(string $path)
	{
		return (bool) preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $path);
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

	public function __construct(string $path)
	{
		if (!self::isValidPath($path)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}

		$this->path = $path;
	}

	public function defaultPath(): ?string
	{
		$path = ROOT . '/skel-dist/web/' . $this->path;

		if (file_exists($path)) {
			return $path;
		}

		return null;
	}

	public function serve(array $params = []): void
	{
		header('Content-Type: text/html;charset=utf-8', true);

		if (Plugin::fireSignal('http.request.skeleton.before', $params)) {
			return;
		}

		if (preg_match(self::TEMPLATE_TYPES, $this->type())) {

			header(sprintf('Content-Type: %s;charset=utf-8', $this->type()));

			try {
				$ut = new UserTemplate('web/' . $this->path);
			}
			catch (\InvalidArgumentException $e) {
				header('HTTP/1.1 404 Not Found', true);

				// Fallback to 404
				$ut = new UserTemplate('web/404.html');;
				$ut->assignArray($params);
				$ut->display();
			}

			try {
				$ut->assignArray($params);
				$ut->display();
			}
			catch (Brindille_Exception $e) {
				printf('<div style="border: 5px solid orange; padding: 10px; background: yellow;"><h2>Erreur dans le squelette</h2><p>%s</p></div>', nl2br(htmlspecialchars($e->getMessage())));
			}
		}
		elseif ($file = $this->file()) {
			$file->serve();
		}
		else {
			header(sprintf('Content-Type: %s;charset=utf-8', $this->type()));
			readfile($this->defaultPath());
		}

		Plugin::fireSignal('http.request.skeleton.after', $params);
	}

	public function file(): ?File
	{
		return Files::get(File::CONTEXT_SKELETON . '/web/' . $this->path);
	}

	public function raw(): string
	{
		if ($file = $this->file()) {
			return $this->file();
		}

		return (string) @file_get_contents($this->defaultPath());
	}

	public function edit(string $content)
	{
		if ($file = $this->file()) {
			$file->setContent($content);
		}
		else {
			File::createAndStore(File::CONTEXT_SKELETON . '/web', $this->path, null, $content);
		}
	}

	public function type(): string
	{
		$ext = substr($this->path, strrpos($this->path, '.')+1);

		switch ($ext) {
			case 'css':
				return 'text/css';
			case 'html':
			case 'htm':
				return 'text/html';
			case 'xml':
				return 'text/xml';
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
}
