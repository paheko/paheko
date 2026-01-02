<?php

namespace Paheko;

use Paheko\Entities\Vault;
use Paheko\Entities\VaultKey;

use KD2\DB\EntityManager as EM;

class Vaults
{
	static public function isSupported(): bool
	{
		return function_exists('sodium_memzero');
	}

	static public function get(int $id): ?Vault
	{
		return EM::findOneById(Vault::class, $id);
	}

	static public function getKey(int $id): ?VaultKey
	{
		return EM::findOneById(VaultKey::class, $id);
	}

	static public function encryptWithPassword(
		#[\SensitiveParameter]
		string $password,
		#[\SensitiveParameter]
		string $data
	): string
	{
		$salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

		$derived_key = sodium_crypto_pwhash(
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
			$password,
			$salt,
			SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
			SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
		);

		$encrypted_data = sodium_crypto_secretbox($data, $nonce, $derived_key);

		sodium_memzero($password);
		sodium_memzero($derived_key);
		sodium_memzero($data);

		return sodium_bin2hex($salt . $nonce . $encrypted_data);
	}

	static public function decryptWithPassword(
		#[\SensitiveParameter]
		string $password,
		#[\SensitiveParameter]
		string $encrypted_data
	): ?string
	{
		$encrypted_data = sodium_hex2bin($encrypted_data);
		$salt = substr($encrypted_data, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);
		$nonce = substr($encrypted_data, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encrypted_data = substr($encrypted_data, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$derived_key = sodium_crypto_pwhash(
			SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
			$password,
			$salt,
			SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
			SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
		);
		sodium_memzero($password);

		$data = sodium_crypto_secretbox_open($encrypted_data, $nonce, $derived_key);
		sodium_memzero($password);
		sodium_memzero($derived_key);
		sodium_memzero($nonce);
		sodium_memzero($salt);
		sodium_memzero($encrypted_data);

		if ($data === false) {
			return null;
		}

		return $data;
	}
}
