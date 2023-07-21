<?php

namespace Paheko;

use Paheko\Entities\API_Credentials as Entity;

use KD2\DB\EntityManager as EM;

class API_Credentials
{
	static public function list(): array
	{
		return EM::getInstance(Entity::class)->all('SELECT * FROM @TABLE ORDER BY key;');
	}

	static public function create(): Entity
	{
		$e = new Entity;
		$e->importForm();
		$e->secret = password_hash($e->secret, \PASSWORD_DEFAULT);
		$e->created = new \DateTime;
		$e->save();
		return $e;
	}

	static public function generateSecret(): string
	{
		return preg_replace('/[^0-9a-z]/i', '', base64_encode(random_bytes(16)));
	}

	static public function generateKey(): string
	{
		return strtolower(substr(self::generateSecret(), 0, 10));
	}

	static public function delete(int $id): void
	{
		$e = EM::findOneById(Entity::class, $id);

		if (!$e) {
			return;
		}

		$e->delete();
	}

	static public function login(string $key, string $secret): ?Entity
	{
		$e = EM::findOne(Entity::class, 'SELECT * FROM @TABLE WHERE key = ?;', $key);

		if (!$e || !password_verify($secret, $e->secret)) {
			return null;
		}

		EM::getInstance(Entity::class)->DB()->exec(sprintf('UPDATE %s SET last_use = datetime() WHERE id = %d;', Entity::TABLE, $e->id()));

		return $e;
	}
}
