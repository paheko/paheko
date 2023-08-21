<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Utils;

use const Paheko\{WWW_URL, ADMIN_URL};

abstract class AbstractRender
{
	protected string $uri;
	protected string $parent;
	protected ?string $path = null;
	protected string $context;

	protected string $link_prefix = '';
	protected string $link_suffix = '';

	public function __construct(?string $path, ?string $user_prefix)
	{
		$this->path = $path;

		if ($path) {
			$this->context = strtok($path, '/');

			if ($this->context === File::CONTEXT_WEB) {
				$this->parent = $path;
				$this->uri = Utils::basename($path);
				$this->link_prefix = $user_prefix ?? WWW_URL;
			}
			else {
				$this->parent = Utils::dirname($path);
				$this->uri = $this->parent;

				if ($this->context === File::CONTEXT_DOCUMENTS) {
					$prefix_path = $this->parent;
				}
				else {
					$prefix_path = File::CONTEXT_DOCUMENTS;
				}

				$this->link_prefix = $user_prefix ?? ADMIN_URL . 'common/files/preview.php?p=' . $prefix_path . '/';
				$this->link_suffix = '.md';
			}

			$this->uri = str_replace('%2F', '/', rawurlencode($this->uri));
		}
	}

	abstract public function render(string $content): string;

	public function hasPath(): bool
	{
		return isset($this->path);
	}

	public function registerAttachment(string $uri)
	{
		Render::registerAttachment($this->path, $uri);
	}

	public function listImages(): array
	{
		if (!$this->path) {
			return [];
		}

		$out = [];
		$list = Files::list($this->parent);

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
		$prefix = $this->uri;
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
}