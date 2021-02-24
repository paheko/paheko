<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\UserException;
use Garradin\UserTemplate\UserTemplate;

use KD2\Brindille_Exception;
use KD2\DB\EntityManager as EM;

use const Garradin\ROOT;

class Skeleton
{
	const TEMPLATE_TYPES = '!^(?:text/(?:html|plain)|\w+/(?:\w+\+)?xml)$!';

	protected $name;
	protected $file;

	public function __construct(string $tpl)
	{
		if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $tpl)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}

		$this->file = Files::get(File::CONTEXT_SKELETON, $tpl);

		$this->name = $tpl;
	}

	public function defaultPath(): ?string
	{
		$path = ROOT . '/www/skel-dist/' . $this->name;

		if (file_exists($path)) {
			return $path;
		}

		return null;
	}

	public function serve(array $params = []): bool
	{
		header('Content-Type: text/html;charset=utf-8', true);

		if (!$this->exists()) {
			header('HTTP/1.1 404 Not Found', true);
			$tpl = new self('404.html');

			if (!$tpl->serve()) {
				throw new UserException('Cette page n\'existe pas.');
			}
		}

		if (preg_match(self::TEMPLATE_TYPES, $this->type())) {
			$ut = new UserTemplate($this->file);

			if (!$this->file) {
				$ut->setSource($this->defaultPath());
			}

			header(sprintf('Content-Type: %s;charset=utf-8', $this->type()));

			try {
				$ut->assignArray($params);
				$ut->display();
			}
			catch (Brindille_Exception $e) {
				printf('<div style="border: 5px solid orange; padding: 10px; background: yellow;"><h2>Erreur dans le squelette</h2><p>%s</p></div>', nl2br(htmlspecialchars($e->getMessage())));
			}
		}
		elseif ($this->file) {
			$this->file->serve();
		}
		else {
			header(sprintf('Content-Type: %s;charset=utf-8', $this->type()));
			readfile($this->defaultPath());
		}


		return true;
	}

	public function fetch(array $params = []): string
	{
		if (!$this->exists()) {
			return '';
		}

		if (preg_match(self::TEMPLATE_TYPES, $this->type())) {
			$ut = new UserTemplate($this->file);

			if (!$this->file) {
				$ut->setSource($this->defaultPath());
			}

			$ut->assignArray($params);

			return $ut->fetch();
		}
		elseif ($this->file) {
			$this->file->fetch();
		}
		else {
			return file_get_contents($this->defaultPath());
		}
	}

	public function display(array $params = []): void
	{
		if (!$this->exists()) {
			return;
		}

		if (preg_match(self::TEMPLATE_TYPES, $this->type())) {
			$ut = new UserTemplate($this->file);

			if (!$this->file) {
				$ut->setSource($this->defaultPath());
			}

			$ut->assignArray($params);

			$ut->display();
		}
		elseif ($this->file) {
			$this->file->display();
		}
		else {
			readfile($this->defaultPath());
		}
	}

	public function exists()
	{
		return $this->file ? true : ($this->defaultPath() ? true : false);
	}

	public function raw(): string
	{
		return $this->file ? $this->file->fetch() : file_get_contents($this->defaultPath());
	}

	public function edit(string $content)
	{
		File::createAndStore(File::CONTEXT_SKELETON, $this->name, null, $content);
	}

	public function type(): string
	{
		$name = $this->file->name ?? $this->defaultPath();
		$ext = substr($name, strrpos($name, '.')+1);

		if ($ext == 'css') {
			return 'text/css';
		}
		elseif ($ext == 'html') {
			return 'text/html';
		}
		elseif ($ext == 'js') {
			return 'text/javascript';
		}

		if ($this->file) {
			return $this->file->type;
		}

		$finfo = \finfo_open(\FILEINFO_MIME_TYPE);
		return finfo_file($finfo, $this->defaultPath());

	}

	public function reset()
	{
		if ($this->file) {
			$this->file->delete();
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

		$dir = dir(ROOT . '/www/skel-dist/');

		while ($file = $dir->read())
		{
			if ($file[0] == '.')
				continue;

			$sources[$file] = null;
		}

		$dir->close();

		$list = Files::list(File::CONTEXT_SKELETON);

		foreach ($list as $file) {
			$sources[$file->name] = $file;
		}

		ksort($sources);

		return $sources;
	}

	static public function upload(string $name, ?string $file): void
	{
		if (empty($_FILES[$file]['tmp_name'])) {
			File::createAndStore($name, File::CONTEXT_SKELETON, null, null, 'À modifier…');
		}
		else {
			$f = File::upload($file, File::CONTEXT_SKELETON, null);

			if ($f->name != $name) {
				$f->importForm(['name' => $name]);
				$f->save();
			}
		}
	}
}
