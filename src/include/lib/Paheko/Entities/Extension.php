<?php

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;
use Paheko\Modules;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\ValidationException;

use Paheko\Entities\Module;
use Paheko\Entities\Plugin;

use const Paheko\{HELP_URL};

class Extension extends Entity
{
	protected string $type;
	protected string $name;

	protected string $label;

	protected ?string $description;
	protected ?string $author;
	protected ?string $author_url;
	protected ?string $doc_url;

	protected bool $enabled;

	protected ?string $broken_message = null;

	protected ?string $icon_url;
	protected string $details_url;
	protected ?string $config_url;
	protected ?string $url = null;

	protected bool $installed;
	protected bool $missing;

	protected ?string $restrict_section;
	protected ?int $restrict_level;

	protected ?\stdClass $ini = null;

	protected ?Plugin $plugin = null;
	protected ?Module $module = null;

	public function isModule(): bool
	{
		return $this->type === 'module';
	}

	public function isPlugin(): bool
	{
		return $this->type === 'plugin';
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function enable()
	{
		$this->{$this->type}->enable();
	}

	public function disable()
	{
		$this->{$this->type}->disable();
	}

	public function delete(): bool
	{
		// We can't delete a plugin that is not installed yet
		if (!$this->installed) {
			return true;
		}

		return $this->{$this->type}->delete();
	}

	public function normalize($item)
	{
		$type = $item instanceof Plugin ? 'plugin' : 'module';
		$this->$type = $item;
		$this->set('type', $type);
		$this->set('restrict_section', $item->restrict_section);
		$this->set('restrict_level', $item->restrict_level);
		$this->set('name', $item->name);
		$this->set('label', $item->label ?? $item->name);
		$this->set('description', $item->description);
		$this->set('enabled', (bool) $item->enabled);
		$this->set('author', $item->author);
		$this->set('author_url', $item->author_url);
		$this->set('icon_url', $item->icon_url());
		$this->set('details_url', Utils::getLocalURL('!config/ext/details.php?name=' . $item->name));
		$this->set('config_url', $item->hasConfig() ? $item->url($item::CONFIG_FILE) : null);
		$this->set('installed', $type === 'plugin' ? $item->exists() : true);
		$this->set('missing', $type === 'plugin' ? !$item->hasCode() : false);
		$this->set('broken_message', $type === 'plugin' ? $item->getBrokenMessage() : null);
		$this->set('ini', $item->getINIProperties());
		$this->set('doc_url', $this->ini->doc_url ?? null);

		if ($item->hasFile($item::INDEX_FILE)) {
			$this->set('url', $item->url($type == 'plugin' ? 'admin/' : ''));
		}
	}

	public function listSnippets(): array
	{
		if ($this->type === 'plugin') {
			return [];
		}

		return $this->module->listSnippets();
	}

	public function getDocTarget(): ?string
	{
		if (!$this->doc_url) {
			return null;
		}

		$help_host = parse_url(HELP_URL, PHP_URL_HOST);
		$doc_host = parse_url($this->doc_url, PHP_URL_HOST);

		if ($help_host === $doc_host) {
			return '_dialog';
		}
		else {
			return '_blank';
		}
	}
}
