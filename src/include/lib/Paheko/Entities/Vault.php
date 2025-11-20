<?php

namespace Paheko\Entities;

use Paheko\Entity;

class Vault extends Entity
{
	const TABLE = 'vaults';

	protected ?int $id;
	protected ?DateTime $expires;
	protected string $data;

	public function decrypt(VaultKey $key): ?string
	{
		$data = sodium_hex2bin($this->data);
		$nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$data = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$private_key = $key->getPrivateKey();

		$decrypted_data = sodium_crypto_secretbox_open($data, $nonce, $private_key);
		sodium_memzero($data);
		sodium_memzero($nonce);

		return $data === false ? null : $data;
	}

	public function encrypt(VaultKey $key, string $data): void
	{
		$nonce = random_bytes(24);
		$message = sodium_crypto_secretbox($data, $nonce, $key->getPrivateKey());
		$data = sodium_bin2hex($nonce . $message);
		$this->set('data', $data);
		sodium_memzero($nonce);
		sodium_memzero($message);
		sodium_memzero($data);
	}
}
