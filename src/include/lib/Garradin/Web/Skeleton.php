<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\UserException;
use Garradin\UserTemplate;

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

		$this->file = Files::getWithNameAndContext($tpl, File::CONTEXT_SKELETON);

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

	public function serve(): bool
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
		if (!$this->file) {
			$this->file = new File;
			$this->file->import([
				'type' => $this->type(),
				'name' => $this->name,
				'image' => 0,
				'public' => 0,
				'context' => File::CONTEXT_SKELETON,
			]);
		}

		$this->file->store(null, $content);
		$this->file->save();
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
		$defaults = [];

		$dir = dir(ROOT . '/www/skel-dist/');

		while ($file = $dir->read())
		{
			if ($file[0] == '.')
				continue;

			$defaults[$file] = null;
		}

		$dir->close();

		$modified_skeletons = EM::getInstance(File::class)->DB()->getGrouped('SELECT name, id, modified FROM files WHERE context = ? ORDER BY name;', File::CONTEXT_SKELETON);

		$sources = array_merge($defaults, $modified_skeletons);
		ksort($sources);

		return $sources;
	}

}
