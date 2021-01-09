<?php

namespace Garradin\Web;

use KD2\Dumbyer;

class Template
{
	static public function dispatchURI()
	{
		$uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		header('HTTP/1.1 200 OK', 200, true);

		if ($pos = strpos($uri, '?'))
		{
			$uri = substr($uri, 0, $pos);
		}
		else
		{
			// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
			$uri = substr($uri, strlen(WWW_URI) - 1);
		}

		if ($uri == '/')
		{
			$skel = 'sommaire.html';
		}
		elseif ($uri == '/feed/atom/')
		{
			header('Content-Type: application/atom+xml');
			$skel = 'atom.xml';
		}
		elseif ($uri == '/favicon.ico')
		{
			header('Location: ' . ADMIN_URL . 'static/icon.png');
			exit;
		}
		elseif (substr($uri, -1) == '/')
		{
			$skel = 'rubrique.html';
			$_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1, -1);
		}
		elseif (preg_match('!^/admin/!', $uri))
		{
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		else
		{
			$_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1);

			if (preg_match('!^[\w\d_-]+$!i', $_GET['uri'])
				&& file_exists(DATA_ROOT . '/www/squelettes/' . strtolower($_GET['uri']) . '.html'))
			{
				$skel = strtolower($_GET['uri']) . '.html';
			}
			else
			{
				$skel = 'article.html';
			}
		}

		$this->display($skel);
	}

	protected $template;

	public function __construct(string $tpl)
	{
		if (!preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $tpl)) {
			throw new \InvalidArgumentException('Invalid template name');
		}

		$this->template = $tpl;
	}

	public function get(): string
	{
		$path = file_exists(DATA_ROOT . '/www/squelettes/' . $this->template)
			? DATA_ROOT . '/www/squelettes/' . $this->template
			: ROOT . '/www/squelettes-dist/' . $this->template;

		return file_get_contents($path);
	}

	public function edit(string $content)
	{
		$path = DATA_ROOT . '/www/squelettes/' . $this->template;

		return file_put_contents($path, $content);
	}

	public function reset()
	{
		if (file_exists(DATA_ROOT . '/www/squelettes/' . $this->template))
		{
			return Utils::safe_unlink(DATA_ROOT . '/www/squelettes/' . $this->template);
		}

		return false;
	}

	static public function list(): array
	{
		if (!file_exists(DATA_ROOT . '/www/squelettes'))
		{
			Utils::safe_mkdir(DATA_ROOT . '/www/squelettes', 0775, true);
		}

		$sources = [];

		$dir = dir(ROOT . '/www/squelettes-dist');

		while ($file = $dir->read())
		{
			if ($file[0] == '.')
				continue;

			if (!preg_match('/\.(?:css|x?html?|atom|rss|xml|svg|txt)$/i', $file))
				continue;

			$sources[$file] = false;
		}

		$dir->close();

		$dir = dir(DATA_ROOT . '/www/squelettes');

		while ($file = $dir->read())
		{
			if ($file[0] == '.')
				continue;

			if (!preg_match('/\.(?:css|x?html?|atom|rss|xml|svg|txt)$/i', $file))
				continue;

			$sources[$file] = [
				// Est-ce que le fichier fait partie de la distribution de Garradin?
				// (Si non pas possible de faire un reset)
				'dist'  =>  array_key_exists($file, $sources),
				'mtime' =>  filemtime($dir->path.'/'.$file),
			];
		}

		$dir->close();

		ksort($sources);

		return $sources;
	}

}
