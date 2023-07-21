<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Utils;

use const Paheko\{WWW_URL, ADMIN_URL};

abstract class AbstractRender
{
	protected $current_path;
	protected $context;
	protected $link_prefix;
	protected $link_suffix;
	protected $user_prefix;

	protected $file;

	public function __construct(?File $file = null, ?string $user_prefix = null)
	{
		$this->file = $file;

		$this->user_prefix = $user_prefix;

		if ($file) {
			$this->isRelativeTo($file);
		}
	}

	abstract public function render(?string $content = null): string;

	public function hasPath(): bool
	{
		return isset($this->current_path);
	}

	public function registerAttachment(string $uri)
	{
		Render::registerAttachment($this->file, $uri);
	}

	public function listImages(): array
	{
		if (!$this->file) {
			return [];
		}

		$out = [];
		$list = Files::list(Utils::dirname($this->file->path));

		foreach ($list as $file) {
			if (!$file->image) {
				continue;
			}

			$out[] = $file->name;
		}

		return $out;
	}

	public function resolveAttachment(string $uri)
	{
		$prefix = $this->current_path;
		$pos = strpos($uri, '/');

		if ($pos === 0) {
			// Absolute URL: treat it as absolute!
			$uri = ltrim($uri, '/');
		}
		else {
			// Handle relative URIs
			$uri = $prefix . '/' . $uri;
		}

		$this->registerAttachment($uri);

		return WWW_URL . $uri;
	}

	public function resolveLink(string $uri) {
		$first = substr($uri, 0, 1);
		if ($first == '/' || $first == '!') {
			return Utils::getLocalURL($uri);
		}

		if (strpos(Utils::basename($uri), '.') === false) {
			$uri .= $this->link_suffix;
		}

		return $this->link_prefix . $uri;
	}

	public function isRelativeTo(File $file) {
		$this->current_path = $file->parent;
		$this->context = $file->context();
		$this->link_suffix = '';

		if ($this->context === File::CONTEXT_WEB) {
			$this->link_prefix = $this->user_prefix ?? WWW_URL;
			$this->current_path = $file->uri();
		}
		else {
			$this->link_prefix = $this->user_prefix ?? sprintf(ADMIN_URL . 'common/files/preview.php?p=%s/', $this->context);
			$this->link_suffix = '.skriv';
		}
	}
}