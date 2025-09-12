<?php

namespace Paheko\Entities\Files;

use Paheko\Users\Session;
use Paheko\Template;
use Paheko\UserException;

trait FilePermissionsTrait
{
	public function validateCanRead(?Session $session = null): void
	{
		if (!$this->canRead($session)) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			throw new UserException('Vous n\'avez pas accès à ce fichier.', 403);
		}
	}

	public function canRead(?Session $session = null): bool
	{
		// Web pages and config files are always public
		if ($this->isPublic()) {
			return true;
		}

		$session ??= Session::getInstance();

		return $session->checkFilePermission($this->path, 'read');
	}

	public function canShare(?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'share');
	}

	public function canWrite(?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'write');
	}

	public function canDelete(?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		// Deny delete of directories in web context
		if ($this->isDir() && $this->context() == self::CONTEXT_WEB) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'delete');
	}

	public function canMoveToTrash(?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'trash');
	}

	public function canMoveTo(string $destination, ?Session $session = null): bool
	{
		return self::canMove($session) && self::canCreate($destination);
	}

	public function canMove(?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'move');
	}

	public function canCopyTo(string $destination, ?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $this->canRead() && self::canCreate($destination);
	}

	public function canCreateDirHere(?Session $session = null)
	{
		if (!$this->isDir()) {
			return false;
		}

		return self::canCreateDir($this->path . '/example', $session);
	}

	static public function canCreateDir(string $path, ?Session $session = null)
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($path, 'mkdir');
	}

	public function canCreateHere(?Session $session = null): bool
	{
		if (!$this->isDir()) {
			return false;
		}

		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'create');
	}

	public function canRename(?Session $session = null): bool
	{
		return $this->canCreate($this->parent ?? '', $session);
	}

	static public function canCreate(string $path, ?Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		$path = rtrim($path, '/') . '/';

		return $session->checkFilePermission($path, 'create');
	}
}
