<?php

namespace Paheko\Entities\Web;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\Form;
use Paheko\Plugins;
use Paheko\Utils;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Web\Render\Render;
use Paheko\Web\Web;
use Paheko\Web\Cache;
use Paheko\UserTemplate\Modifiers;
use Paheko\Users\DynamicFields;

use KD2\DB\EntityManager as EM;
use KD2\HTTP;

use const Paheko\{WWW_URL, ADMIN_URL};

class Page extends Entity
{
	const NAME = 'Page du site web';
	const PRIVATE_URL = '!web/?id=%d';

	const TABLE = 'web_pages';

	protected int $id;
	protected ?int $id_parent = null;
	protected string $uri;
	protected string $title;
	protected int $type;
	protected int $status;
	protected int $inherited_status;
	protected string $format;
	protected \DateTime $published;
	protected \DateTime $modified;
	protected string $content;

	protected string $_path;
	protected array $_breadcrumbs;

	const FORMATS_LIST = [
		Render::FORMAT_MARKDOWN => 'MarkDown',
		Render::FORMAT_ENCRYPTED => 'Chiffré',
		Render::FORMAT_SKRIV => 'SkrivML',
	];

	const STATUS_DRAFT = 0;
	const STATUS_PRIVATE = 1;
	const STATUS_ONLINE = 2;

	const STATUS_LIST = [
		self::STATUS_DRAFT => 'Brouillon',
		self::STATUS_ONLINE => 'En ligne',
		self::STATUS_PRIVATE => 'Réservée aux membres',
	];

	const TYPE_CATEGORY = 1;
	const TYPE_PAGE = 2;

	const TYPES = [
		self::TYPE_CATEGORY => 'Category',
		self::TYPE_PAGE => 'Page',
	];

	const TEMPLATES = [
		self::TYPE_PAGE => 'article.html',
		self::TYPE_CATEGORY => 'category.html',
	];

	const DUPLICATE_URI_ERROR = 42;

	protected ?File $_dir = null;
	protected ?array $_attachments = null;
	protected ?array $_tagged_attachments = null;
	protected ?string $_html = null;

	static public function create(int $type, ?int $id_parent, string $title, string $status = self::STATUS_ONLINE): self
	{
		$page = new self;
		$data = compact('type', 'id_parent', 'title', 'status');
		$data['content'] = '';

		$page->importForm($data);
		$page->published = new \DateTime;
		$page->modified = new \DateTime;
		$page->type = $type;

		$db = DB::getInstance();
		if ($db->test(self::TABLE, 'uri = ?', $page->uri)) {
			$page->importForm(['uri' => $page->uri . date('-Y-m-d-His')]);
		}

		return $page;
	}

	public function dir(bool $force_reload = false): File
	{
		if (null === $this->_dir || $force_reload) {
			$this->_dir = Files::get(File::CONTEXT_WEB . '/' . $this->uri);

			if (null === $this->_dir) {
				$this->_dir = Files::mkdir(File::CONTEXT_WEB . '/' . $this->uri);
			}
		}

		return $this->_dir;
	}

	public function url(): string
	{
		return WWW_URL . $this->uri;
	}

	public function template(): string
	{
		return self::TEMPLATES[$this->type];
	}

	public function asTemplateArray(): array
	{
		$out = $this->asArray();
		$out['path'] = $this->path();
		$out['parent'] = Utils::dirname($out['path']);
		$out['url'] = $this->url();
		$out['html'] = trim($this->content) !== '' ? $this->render() : '';
		$out['has_attachments'] = $this->hasAttachments();
		return $out;
	}

	public function render(bool $admin = false): string
	{
		$user_prefix = $admin ? ADMIN_URL . 'web/?uri=' : null;

		$this->_html ??= Render::render($this->format, $this->dir_path(), $this->content, $user_prefix);

		return $this->_html;
	}

	public function excerpt(int $length = 500): string
	{
		return $this->preview(Modifiers::truncate($this->content, $length));
	}

	public function requiresExcerpt(int $length = 500): bool
	{
		return mb_strlen($this->content) > $length;
	}

	public function preview(string $content): string
	{
		$user_prefix = ADMIN_URL . 'web/?uri=';
		return Render::render($this->format, $this->dir_path(), $content, $user_prefix);
	}

	public function dir_path(): string
	{
		return File::CONTEXT_WEB . '/' . $this->uri;
	}

	/**
	 * Return page path according to parents, eg. category_uri/sub_category_uri/page_uri
	 */
	public function path(): string
	{
		if (!isset($this->_path)) {
			$sql = sprintf('SELECT GROUP_CONCAT(uri, \'/\') FROM (%s);',
				rtrim(sprintf(Web::BREADCRUMBS_SQL, $this->id()), '; ')
			);

			$this->_path = DB::getInstance()->firstColumn($sql);
		}

		return $this->_path;
	}

	/**
	 * Get page status, trying to find it from parent pages if different from online
	 */
	public function getInheritedStatus(): ?string
	{
		foreach (array_reverse($this->getBreadcrumbs()) as $page) {
			if ($page->status !== self::STATUS_ONLINE) {
				return $page->status;
			}
		}

		return $page->status ?? null;
	}

	public function getStatus(): string
	{
		if ($this->status !== self::STATUS_ONLINE) {
			return $this->status;
		}

		return $this->inherited_status;
	}

	public function listVersions(): DynamicList
	{
		$name_field = DynamicFields::getNameFieldsSQL('u');

		$columns = [
			'id' => ['select' => 'v.id'],
			'id_user' => ['select' => 'v.id_user'],
			'date' => [
				'select' => 'v.date',
				'label' => 'Date',
			],
			'author' => [
				'label' => 'Auteur',
				'select' => $name_field,
			],
			'size' => [
				'label' => 'Longueur du texte',
				'select' => 'v.size',
			],
			'changes' => [
				'label' => 'Évolution',
				'select' => 'v.changes',
			],
		];

		$tables = 'web_pages_versions v
			LEFT JOIN users u ON u.id = v.id_user';
		$conditions = sprintf('v.id_page = %d', $this->id());
		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('id', true);

		return $list;
	}

	public function getVersion(int $id): ?\stdClass
	{
		return DB::getInstance()->first('SELECT a.*,
			IFNULL((SELECT content FROM web_pages_versions WHERE id_page = a.id_page AND id < a.id ORDER BY id DESC LIMIT 1), \'\') AS previous_content
			FROM web_pages_versions a
			WHERE a.id_page = ? AND a.id = ?;', $this->id(), $id) ?: null;
	}

	public function syncSearch(): void
	{
		if ($this->format == Render::FORMAT_ENCRYPTED) {
			$content = null;
		}
		else {
			$content = $this->render();
		}

		$this->dir()->indexForSearch($content, $this->title, 'text/html');
	}

	public function saveNewVersion(?int $user_id): bool
	{
		$content_modified = $this->isModified('content');
		$prev_content = $this->getModifiedProperty('content');

		$r = $this->save();

		if ($content_modified) {
			$db = DB::getInstance();
			$l = mb_strlen($this->content);

			$version = [
				'id_user' => $user_id,
				'id_page' => $this->id(),
				'date'    => new \DateTime,
				'content' => $this->content,
				'size'    => $l,
				'changes' => $l - mb_strlen($prev_content),
			];

			$db->insert('web_pages_versions', $version);
			$version['id'] = $db->lastInsertId();

			Plugins::fire('web.page.version.new', false, [
				'entity'      => $this,
				'content'     => $this->content,
				'old_content' => $prev_content,
				'version'     => (object) $version,
			]);
		}

		return $r;
	}

	public function save(bool $selfcheck = true): bool
	{
		$dir = null;

		if (!$this->exists() || $this->isModified('status')) {
			$this->set('inherited_status', $this->status);
		}

		if ($this->isModified('uri')) {
			$dir = Files::get(File::CONTEXT_WEB . '/' . $this->getModifiedProperty('uri'));
		}

		// Update modified date if required
		if (count($this->_modified) && !isset($this->_modified['modified'])) {
			$this->set('modified', new \DateTime);
		}

		$update_search = $this->isModified('content') || $this->isModified('title');
		$update_children = ($this->isModified('status') && $this->type === self::TYPE_CATEGORY) || $this->isModified('id_parent');

		parent::save($selfcheck);

		if ($dir) {
			$dir->rename($this->dir_path());
		}

		if ($update_children) {
			Web::updateChildrenInheritedStatus();
		}

		if ($update_search) {
			$this->syncSearch();
		}

		Cache::clear();

		return true;
	}

	public function delete(): bool
	{
		$dir = $this->dir();

		$r = parent::delete();

		if ($r && $dir) {
			$dir->delete();
		}

		Cache::clear();
		return $r;
	}

	public function selfCheck(): void
	{
		$db = DB::getInstance();
		$this->assert($this->type === self::TYPE_CATEGORY || $this->type === self::TYPE_PAGE, 'Unknown page type');
		$this->assert(array_key_exists($this->status, self::STATUS_LIST), 'Unknown page status');
		$this->assert(array_key_exists($this->format, self::FORMATS_LIST), 'Unknown page format');
		$this->assert(trim($this->title) !== '', 'Le titre ne peut rester vide');
		$this->assert(mb_strlen($this->title) <= 200, 'Le titre ne peut faire plus de 200 caractères');
		$this->assert(trim($this->uri) !== '', 'L\'URI ne peut rester vide');
		$this->assert(strlen($this->uri) <= 150, 'L\'URI ne peut faire plus de 150 caractères');

		if ($this->exists()) {
			$this->assert($this->id_parent !== $this->id(), 'Invalid parent page');
		}

		$this->assert(!$this->exists() || !$db->test(self::TABLE, 'uri = ? AND id != ?', $this->uri, $this->id()), 'Cette adresse URI est déjà utilisée par une autre page, merci d\'en choisir une autre : ' . $this->uri, self::DUPLICATE_URI_ERROR);
		$this->assert($this->exists() || !$db->test(self::TABLE, 'uri = ?', $this->uri), 'Cette adresse URI est déjà utilisée par une autre page, merci d\'en choisir une autre : ' . $this->uri, self::DUPLICATE_URI_ERROR);

		if (isset($this->id_parent) && $this->exists()) {
			$this->assert($this->id_parent !== $this->id());

			$breadcrumbs = Web::getBreadcrumbs($this->id_parent);

			foreach ($breadcrumbs as $bc) {
				$this->assert($bc->id != $this->id, 'Il n\'est pas possible de mettre une page dans une sous-catégorie d\'elle-même.');
			}

			$this->assert(count($breadcrumbs) < 50, 'Il n\'est pas possible d\'avoir plus de 50 niveaux de sous-catégories.');
		}
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['date']) && isset($source['date_time'])) {
			$source['published'] = $source['date'] . ' ' . $source['date_time'];
		}

		if (isset($source['title']) && !$this->exists()) {
			$source['uri'] = Modifiers::remove_leading_number($source['title']);
		}

		if (isset($source['uri'])) {
			$source['uri'] = Utils::transformTitleToURI($source['uri']);

			if (!$this->exists()) {
				$source['uri'] = strtolower($source['uri']);
			}
		}

		if (array_key_exists('id_parent', $source) && is_array($source['id_parent'])) {
			$source['id_parent'] = Form::getSelectorValue($source['id_parent']) ?: null;
		}

		if (!empty($source['encryption']) ) {
			$this->set('format', Render::FORMAT_ENCRYPTED);
		}
		elseif (empty($source['format'])) {
			$this->set('format', Render::FORMAT_MARKDOWN);
		}

		return parent::importForm($source);
	}

	public function getBreadcrumbs(): array
	{
		$this->_breadcrumbs ??= Web::getBreadcrumbs($this->id());
		return $this->_breadcrumbs;
	}

	public function listAttachments(): array
	{
		if (null === $this->_attachments) {
			$list = Files::list($this->dir_path());

			// Remove sub-directories
			$list = array_filter($list, fn ($a) => $a->type != $a::TYPE_DIRECTORY);

			$this->_attachments = $list;
		}

		return $this->_attachments;
	}

	/**
	 * List attachments that are cited in the text content
	 */
	public function listTaggedAttachments(): array
	{
		if (null === $this->_tagged_attachments) {
			$this->render();
			$this->_tagged_attachments = Render::listAttachments($this->dir_path());
		}

		return $this->_tagged_attachments;
	}

	/**
	 * List attachments that are *NOT* cited in the text content
	 */
	public function listOrphanAttachments(): array
	{
		$used = $this->listTaggedAttachments();
		$orphans = [];

		foreach ($this->listAttachments() as $file) {
			if (!in_array($file->uri(), $used)) {
				$orphans[] = $file->uri();
			}
		}

		return $orphans;
	}

	public function hasAttachments(): bool
	{
		foreach ($this->listAttachments() as $attachment) {
			return true;
		}

		return false;
	}

	/**
	 * Return list of images
	 * If $all is FALSE then this will only return images that are not present in the content
	 */
	public function getImageGallery(bool $all = true): array
	{
		return $this->getAttachmentsGallery($all, true);
	}

	/**
	 * Return list of files
	 * If $all is FALSE then this will only return files that are not present in the content
	 */
	public function getAttachmentsGallery(bool $all = true, bool $images = false): array
	{
		$out = [];
		$tagged = [];

		if (!$all) {
			$tagged = $this->listTaggedAttachments();
		}

		foreach ($this->listAttachments() as $a) {
			if ($images && !$a->isImage()) {
				continue;
			}
			elseif (!$images && $a->isImage()) {
				continue;
			}

			// Skip
			if (!$all && in_array($a->name, $tagged)) {
				continue;
			}

			$out[] = $a;
		}

		return $out;
	}

	/**
	 * Return list of internal links in page that link to non-existing pages
	 */
	public function checkInternalPagesLinks(?array &$pages = null): array
	{
		if ($this->format == Render::FORMAT_ENCRYPTED) {
			return [];
		}

		$renderer = Render::getRenderer($this->format, $this->dir_path());
		$renderer->render($this->content);
		$errors = [];

		foreach ($renderer->listLinks() as $link) {
			if ($link['type'] !== 'page') {
				continue;
			}

			$uri = strtok($link['uri'], '#');
			strtok('');

			if (null !== $pages && !array_key_exists($uri, $pages)) {
				$errors[$uri] = $link['label'];
			}
			elseif (null === $pages && !Web::getByURI($uri)) {
				$errors[$uri] = $link['label'];
			}
		}

		return $errors;
	}

	public function hasSubPages(): bool
	{
		return DB::getInstance()->test('web_pages', 'id_parent = ?', $this->id());
	}

	public function toggleType(): void
	{
		$has_sub_pages = $this->hasSubPages();

		if ($has_sub_pages) {
			$this->set('type', self::TYPE_CATEGORY);
		}
		elseif ($this->type == self::TYPE_CATEGORY) {
			$this->set('type', self::TYPE_PAGE);
		}
		else {
			$this->set('type', self::TYPE_CATEGORY);
		}
	}

	public function isCategory(): bool
	{
		return $this->type == self::TYPE_CATEGORY;
	}

	public function isOnline(): bool
	{
		return $this->status !== self::STATUS_DRAFT;
	}
}
