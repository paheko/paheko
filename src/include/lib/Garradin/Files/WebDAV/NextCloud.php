<?php

namespace Garradin\Files\WebDAV;

use KD2\WebDAV\NextCloud as WebDAV_NextCloud;
use KD2\WebDAV\Exception as WebDAV_Exception;

use Garradin\Files\Files;

use const Garradin\{SECRET_KEY, ADMIN_URL, CACHE_ROOT, WWW_URL};

class NextCloud extends WebDAV_NextCloud
{
	protected string $temporary_chunks_path;
	protected string $prefix = 'documents/';

	public function __construct()
	{
		$this->temporary_chunks_path =  CACHE_ROOT . '/webdav.chunks';
		$this->setRootURL(WWW_URL);
	}

	public function auth(?string $login, ?string $password): bool
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			return true;
		}

		if (!$login || !$password) {
			return false;
		}

		if ($session->checkAppCredentials($login, $password)) {
			return true;
		}

		if ($session->login($login, $password)) {
			return true;
		}

		return false;
	}

	public function getUserName(): ?string
	{
		$s = Session::getInstance();
		return $s->isLogged() ? $s->user()->name() : null;
	}

	public function setUserName(string $login): bool
	{
		return true;
	}

	public function getUserQuota(): array
	{
		return [
			'free'  => Files::getRemainingQuota(),
			'used'  => Files::getUsedQuota(),
			'total' => Files::getQuota(),
		];
	}

	public function generateToken(): string
	{
		return Session::getInstance()->generateAppToken();
	}

	public function validateToken(string $token): ?array
	{
		return Session::getInstance()->verifyAppToken($_POST['token']);
	}

	public function getLoginURL(?string $token): string
	{
		if ($token) {
			return sprintf('%slogin.php?app=%s', ADMIN_URL, $token);
		}
		else {
			return sprintf('%slogin.php?app=redirect', ADMIN_URL);
		}
	}

	public function getDirectDownloadSecret(string $uri, string $login): string
	{
		return hash_hmac('sha1', $uri, SECRET_KEY);
	}

	protected function cleanChunks(): void
	{
		// 36 hours
		$expire = time() - 36*3600;

		foreach (glob($this->temporary_chunks_path . '/*') as $dir) {
			$first_file = current(glob($dir . '/*'));

			if (filemtime($first_file) < $expire) {
				Utils::deleteRecursive($dir, true);
			}
		}
	}

	public function storeChunk(string $login, string $name, string $part, $pointer): void
	{
		$this->cleanChunks();

		$path = $this->temporary_chunks_path . '/' . $name;
		@mkdir($path, 0777, true);

		$file_path = $path . '/' . $part;
		$out = fopen($file_path, 'wb');
		$quota = $this->getUserQuota();

		$used = array_sum(array_map(fn($a) => filesize($a), glob($path . '/*')));
		$used += $quota['used'];

		while (!feof($pointer)) {
			$data = fread($pointer, 8192);
			$used += strlen($used);

			if ($used > $quota['free']) {
				$this->deleteChunks($login, $name);
				throw new WebDAV_Exception('Your quota does not allow for the upload of this file', 403);
			}

			fwrite($out, $data);
		}

		fclose($out);
		fclose($pointer);
	}

	public function deleteChunks(string $login, string $name): void
	{
		$path = $this->temporary_chunks_path . '/' . $name;
		Utils::deleteRecursive($path, true);
	}

	public function assembleChunks(string $login, string $name, string $target, ?int $mtime): array
	{
		$parent = Utils::dirname($target);
		$parent = Files::get($parent);

		if (!$parent || $parent->type != $parent::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target parent directory does not exist', 409);
		}

		$path = $this->temporary_chunks_path . '/' . $name;
		$tmp_file = $path . '/__complete';

		$exists = Files::exists($target);

		try {
			$out = fopen($tmp_file, 'wb');

			foreach (glob($path . '/*') as $file) {
				$in = fopen($file, 'rb');

				while (!feof($in)) {
					fwrite($out, fread($in, 8192));
				}

				fclose($in);
			}

			$file = Files::createFromPointer($target, $out);

			fclose($out);

			if ($mtime) {
				$file->touch($mtime);
			}
		}
		finally {
			$this->deleteChunks($login, $name);
			Utils::safe_unlink($tmp_file);
		}

		return ['created' => !$exists, 'etag' => $file->etag()];
	}

	protected function nc_avatar(): ?array
	{
		header('Location: ' . WWW_URL . '/config/icon.png');
		return null;
	}
}
