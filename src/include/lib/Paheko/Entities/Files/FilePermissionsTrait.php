<?php

namespace Paheko\Entities\Files;

use Paheko\Users\Session;
use Paheko\Template;
use Paheko\UserException;

trait FilePermissionsTrait
{
	public function validateCanRead(?Session $session = null, ?string $share_hash = null, ?string $share_password = null): void
	{
		if ($share_hash) {
			if ($this->checkShareLink($share_hash, $share_password)) {
				return;
			}

			if ($this->checkShareLinkRequiresPassword($share_hash)) {
				$tpl = Template::getInstance();
				$has_password = (bool) $share_password;

				$tpl->assign(compact('has_password'));
				$tpl->display('ask_share_password.tpl');
				return;
			}
		}

		if (!$this->canRead($session)) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			throw new UserException('Vous n\'avez pas accès à ce fichier.', 403);
			return;
		}
	}

	public function canRead(Session $session = null): bool
	{
		// Web pages and config files are always public
		if ($this->isPublic()) {
			return true;
		}

		$session ??= Session::getInstance();

		return $session->checkFilePermission($this->path, 'read');
	}

	public function canShare(Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'share');
	}

	public function canWrite(Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'write');
	}

	public function canDelete(Session $session = null): bool
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

	public function canMoveTo(string $destination, Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'move') && $this->canDelete() && self::canCreate($destination);
	}

	public function canCopyTo(string $destination, Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $this->canRead() && self::canCreate($destination);
	}

	public function canCreateDirHere(Session $session = null)
	{
		if (!$this->isDir()) {
			return false;
		}

		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($this->path, 'mkdir');
	}

	static public function canCreateDir(string $path, Session $session = null)
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($path, 'mkdir');
	}

	public function canCreateHere(Session $session = null): bool
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

	public function canRename(Session $session = null): bool
	{
		return $this->canCreate($this->parent ?? '', $session);
	}

	static public function canCreate(string $path, Session $session = null): bool
	{
		$session ??= Session::getInstance();

		if (!$session->isLogged()) {
			return false;
		}

		return $session->checkFilePermission($path, 'create');
	}
}
