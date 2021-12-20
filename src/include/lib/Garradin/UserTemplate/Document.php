<?php

namespace Garradin\UserTemplate;

use Garradin\Membres\Session;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use KD2\Brindille_Exception;

use const Garradin\{ROOT, WWW_URL};

class Document
{
	const CONFIG_FILE = 'config.json';

	const CONTEXT_TRANSACTION = 'transaction';
	const CONTEXT_USER = 'user';
	const CONTEXT_WEB = 'web';

	const CONTEXTS = [
		self::CONTEXT_WEB => 'Site web',
		self::CONTEXT_TRANSACTION => 'Écriture',
		self::CONTEXT_USER => 'Membre',
	];


	protected bool $dist;
	protected string $context;
	protected string $id;
	protected string $path;
	public $config;
	public string $name;

	static public function fromURI(string $uri)
	{
		return new self(strtok($uri, '/'), strtok(''));
	}

	static public function serve(string $uri): void
	{
		$session = Session::getInstance();

		if (!$session->isLogged()) {
			http_response_code(403);
			throw new UserException('Merci de vous connecter pour accéder à ce document.');
		}

		$path = substr($uri, 0, strrpos($uri, '/'));
		$file = substr($uri, strrpos($uri, '/') + 1) ?: 'index.html';

		$doc = self::fromURI($path);

		try {
			if (isset($_GET['pdf'])) {
				$doc->PDF($file);
			}
			else {
				$doc->display($file);
			}
		}
		catch (\InvalidArgumentException $e) {
			http_response_code(404);
			throw new UserException('Cette page de document n\'existe pas');
		}
	}

	public function __construct(string $context, string $id)
	{
		if (!array_key_exists($context, self::CONTEXTS)) {
			throw new \InvalidArgumentException('Invalid context');
		}

		if ($context != self::CONTEXT_TRANSACTION && $context != self::CONTEXT_USER) {
			throw new \InvalidArgumentException('Invalid context');
		}

		$path = $context . '/' . $id;

		if (Files::exists(File::CONTEXT_SKELETON . '/' . $path)) {
			$f = Files::get($path . '/' . self::CONFIG_FILE);

			if (!$f) {
				throw new UserException(sprintf('Fichier "%s" manquant dans "%s"', self::CONFIG_FILE, $path));
			}

			$config = $f->fetch();
			$this->dist = false;
		}
		else {
			$config_path = ROOT . '/skel-dist/' . $path . '/' . self::CONFIG_FILE;

			$config = @file_get_contents($config_path);

			if (!$config) {
				throw new UserException(sprintf('Fichier "%s" manquant dans "skel-dist/%s"', self::CONFIG_FILE, $path));
			}

			$this->dist = true;
		}

		$this->config = json_decode($config);

		if (!isset($this->config->name)) {
			throw new UserException('Le nom du document n\'est pas défini dans ' . self::CONFIG_FILE);
		}

		$this->name = $this->config->name;
		$this->context = $context;
		$this->id = $id;
		$this->path = $path;
	}

	static public function list(string $context)
	{
		$documents = [];

		$path = ROOT . '/skel-dist/' . $context;
		$i = new \DirectoryIterator($path);

		foreach ($i as $file) {
			if ($file->isDot() || !$file->isDir()) {
				continue;
			}

			$documents[$file->getFilename()] = null;
		}

		unset($i);

		$list = Files::list(File::CONTEXT_SKELETON . '/' . $context);

		foreach ($list as $file) {
			if ($file->type != $file::TYPE_DIRECTORY) {
				continue;
			}

			$documents[$file] = null;
		}

		ksort($documents);

		foreach ($documents as $key => &$doc) {
			$doc = new self($context, $key);
		}

		return $documents;
	}

	public function template(string $file)
	{
		if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $file)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}

		$ut = new UserTemplate($this->path . '/' . $file);

		return $ut;
	}

	public function url(string $file = '', array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		return sprintf('%sdoc/%s/%s/%s%s', WWW_URL, $this->context, $this->id, $file, $params);
	}

	public function display(string $file)
	{
		header('Content-Type: text/html;charset=utf-8', true);

		try {
			$this->template($file)->display();
		}
		catch (Brindille_Exception $e) {
			printf('<div style="border: 5px solid orange; padding: 10px; background: yellow;"><h2>Erreur dans le code du document</h2><p>%s</p></div>', nl2br(htmlspecialchars($e->getMessage())));
		}
	}

	public function PDF(string $file)
	{
		$this->template($file)->displayPDF();
	}
}