<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Files\Folders;
use Garradin\Entities\Files\File;
use Garradin\UserException;
use Garradin\UserTemplate;

use const Garradin\ROOT;

class Template
{
	const TEMPLATE_TYPES = '!^(?:text/\w+|\w+/(?:\w+\+)?xml)$!';

	protected $template;
	protected $file;

	static public function display(string $file)
	{
		header('Content-Type: text/html;charset=utf-8', true);

		$tpl = new self($file);

		if (!$tpl->serve()) {
			header('HTTP/1.1 404 Not Found', true);
			$tpl = new self('404.html');

			if (!$tpl->serve()) {
				throw new UserException('Cette page n\'existe pas.');
			}
		}
	}

	public function __construct(string $tpl)
	{
		if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $tpl)) {
			throw new \InvalidArgumentException('Invalid template name');
		}

		$this->file = Files::getSystemFile($tpl, Folders::TEMPLATES);

		if ($this->file && !$this->file->public) {
			throw new \InvalidArgumentException('This file is not public');
		}

		$this->template = $tpl;
	}

	public function defaultPath(): ?string
	{
		$path = ROOT . '/www/skel-dist/' . $this->template;

		if (file_exists($path)) {
			return $path;
		}

		return null;
	}

	public function serve(): bool
	{
		if (!$this->exists()) {
			return false;
		}

		if (preg_match(self::TEMPLATE_TYPES, $this->type())) {
			$ut = new UserTemplate($this->file);

			if (!$this->file) {
				$ut->setSource($this->defaultPath());
			}

			header(sprintf('Content-Type: %s;charset=utf-8', $this->type()));

			$ut->display($this->raw());
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
			$this->file->set('type', $this->type());
		}

		$this->file->store(null, $content);
	}

	public function type(): string
	{
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

		$templates = Files::listSystemFiles(Folders::TEMPLATES);

		$sources = array_merge($defaults, $templates);
		ksort($sources);

		return $sources;
	}

}
