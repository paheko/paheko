<?php

namespace Paheko\Entities\Files;

use KD2\Security;

use const Paheko\{SECRET_KEY};

/**
 * @deprecated FIXME: delete for 1.5.0
 * This is for handling older file sharing links
 */
trait FileSharingTrait
{
	protected function _createShareHash(int $expiry, ?string $password): string
	{
		$password = trim((string)$password) ?: null;

		$str = sprintf('%s:%s:%s:%s', SECRET_KEY, $this->path, $expiry, $password);

		$hash = hash('sha256', $str, true);
		$hash = substr($hash, 0, 10);
		$hash = Security::base64_encode_url_safe($hash);
		return $hash;
	}

	public function checkShareLinkRequiresPassword(string $str): bool
	{
		return substr($str, 0, 1) == ':';
	}

	public function checkShareLink(string $str, ?string $password): bool
	{
		$str = ltrim($str, ':');

		$hash = strtok($str, ':');
		$expiry = strtok('');

		if (!ctype_alnum($expiry)) {
			return false;
		}

		$expiry = (int)base_convert($expiry, 36, 10);
		$expiry += intval(gmmktime(0, 0, 0, 8, 1, 2022) / 3600);

		if ($expiry < time()/3600) {
			return false;
		}

		$hash_check = $this->_createShareHash($expiry, $password);

		return hash_equals($hash, $hash_check);
	}
}
