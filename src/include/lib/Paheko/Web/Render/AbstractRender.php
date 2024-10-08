<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Utils;

use const Paheko\{WWW_URI, ADMIN_URL};

abstract class AbstractRender
{
	protected string $uri;
	protected string $parent;
	protected ?string $path = null;
	protected string $context;
	protected array $attachments;

	protected string $link_prefix = '';
	protected string $link_suffix = '';

	protected array $links = [];

	protected string $html;
	protected string $content_hash;

	public function __construct(?string $path, ?string $user_prefix)
	{
		$this->path = $path;

		if ($path) {
			$this->context = strtok($path, '/');
			strtok('');

			if ($this->context === File::CONTEXT_WEB) {
				$this->parent = $path;
				$this->uri = Utils::basename($path);
				$this->link_prefix = $user_prefix ?? WWW_URI;
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

	public function render(?string $content): string
	{
		if (null === $content || $content === '') {
			return '';
		}

		$hash = md5($content);

		if (isset($this->html, $this->content_hash) && $hash === $this->content_hash) {
			$out = $this->html;
		}
		else {
			$out = $this->renderUncached($content);

			if ($hash) {
				$this->html = $out;
				$this->content_hash = $hash;
			}
		}

		$out = $this->outputHTML($out);

		return $out;
	}

	abstract public function renderUncached(string $content): string;

	public function hasPath(): bool
	{
		return isset($this->path);
	}

	public function registerAttachment(string $uri)
	{
		Render::registerAttachment($this->path, $uri);
	}

	public function listLinks(): array
	{
		return $this->links;
	}

	public function listAttachments(): array
	{
		if (!isset($this->parent)) {
			return [];
		}

		$this->loadAttachments();

		return $this->attachments;
	}

	protected function loadAttachments(): void
	{
		if (isset($this->attachments)) {
			return;
		}

		$this->attachments = Files::list($this->parent);
	}

	public function listImagesFilenames(): array
	{
		$out = array_filter($this->listAttachments(), fn ($a) => $a->image);
		array_walk($out, fn(&$a) => $a = $a->name);
		return $out;
	}

	public function resolveAttachment(string $uri): ?File
	{
		if (!isset($this->uri)) {
			return null;
		}

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

		$this->loadAttachments();

		$uri = ltrim($uri, '/');

		$attachment = null;
		$context = strtok($this->path, '/');
		strtok('');
		$uri = explode('/', $uri);
		$uri = array_map('rawurldecode', $uri);
		$path = implode('/', $uri);

		$is_context = count($uri) !== 1 && array_key_exists($uri[0], File::CONTEXTS_NAMES);

		// Attachment is in another page of the website: /page-name/file-name.jpg
		// => add web context to path
		if (count($uri) === 2 && !$is_context) {
			$attachment = Files::get(File::CONTEXT_WEB . '/' . $path);
		}

		if (count($uri) === 1 && $context === File::CONTEXT_WEB) {
			$attachment = $this->attachments[$path] ?? null;
		}
		// Attachment is one of the contexts
		elseif (count($uri) !== 1 && !$attachment && $is_context) {
			$attachment = Files::get($path);
		}
		// Attachment is in none of the contexts, consider it is inside the web module
		// (legacy files)
		elseif (count($uri) !== 1 && !$is_context && !$attachment) {
			$attachment = Files::get(File::CONTEXT_MODULES . '/web/' . $path);
		}

		$uri = array_map('rawurlencode', $uri);
		$uri = implode('/', $uri);

		// Try to match with URL-encoded path
		if (!$attachment && $prefix === File::CONTEXT_WEB) {
			foreach ($this->listAttachments() as $file) {
				if ($file->uri() === $uri) {
					$attachment = $file;
					break;
				}
			}
		}

		if (!$attachment) {
			return null;
		}

		$this->registerAttachment($uri);

		return $attachment;
	}

	public function outputHTML(string $content): string
	{
		$content = trim($content);

		if ($content === '') {
			return $content;
		}

		$content = preg_replace_callback(';(<a\s+[^>]*)(href=["\']?((?!#)[^\s\'"]+)[\'"]?)([^>]*>)(.*?)</a>;is', function ($match) {
			$label = trim(html_entity_decode(strip_tags($match[5]))) ?: null;
			$href = sprintf(' href="%s"', htmlspecialchars($this->resolveLink(htmlspecialchars_decode($match[3]), $label)));
			return $match[1] . $href . $match[4] . $match[5] . '</a>';
		}, $content);

		$content = '<div class="web-content">' . $content . '</div>';
		return $content;
	}

	public function resolveLink(string $uri, ?string $label = null): string
	{
		$first = substr($uri, 0, 1);

		if ($first === '/' || $first === '!') {
			$uri = $first === '!' ? Utils::getLocalURL($uri) : $uri;
			$this->links[] = ['type' => 'internal', 'uri' => $uri, 'label' => $label];
			return $uri;
		}

		$pos = strpos($uri, ':');

		if ($pos !== false && (substr($uri, 0, 7) === 'http://' || substr($uri, 0, 8) === 'https://')) {
			$this->links[] = ['type' => 'external', 'uri' => $uri, 'label' => $label];
			return $uri;
		}
		elseif ($pos !== false) {
			$this->links[] = ['type' => 'other', 'uri' => $uri, 'label' => $label];
			return $uri;
		}
		else {
			$this->links[] = ['type' => 'page', 'uri' => $uri, 'label' => $label];
		}

		if (strpos(Utils::basename($uri), '.') === false) {
			$uri .= $this->link_suffix;
		}

		return $this->link_prefix . $uri;
	}
}