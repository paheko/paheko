<?php

namespace Paheko;
use SQLite3;

class SharedKeyValueCache
{
	static protected ?self $_instance = null;
	protected ?SQLite3 $db = null;
	protected ?SQLite3Stmt $write_statement = null;

	static public function getInstance()
	{
		self::$_instance ??= new self;
		return self::$_instance;
	}

	public function __construct()
	{
		if (KEY_VALUE_CACHE_ENGINE === 'sqlite') {
			$file = SHARED_CACHE_ROOT . '/kvcache.sqlite';
			$exists = file_exists($file);
			$this->db = new SQLite3($file);

			// 5 second
			$this->db->busyTimeout(5 * 1000);

			$mode = strtoupper(SQLITE_JOURNAL_MODE);
			$set_mode = $this->db->querySingle('PRAGMA journal_mode;');
			$set_mode = strtoupper($set_mode);

			if ($set_mode !== $mode) {
				// WAL = performance enhancement
				// see https://www.cs.utexas.edu/~jaya/slides/apsys17-sqlite-slides.pdf
				// https://ericdraken.com/sqlite-performance-testing/
				$this->db->exec(sprintf(
					'PRAGMA journal_mode = %s; PRAGMA synchronous = NORMAL; PRAGMA journal_size_limit = %d;',
					$mode,
					32 * 1024 * 1024
				));
			}

			if (!$exists) {
				$this->db->exec('CREATE TABLE store (key TEXT NOT NULL, value NULL, expiry INTEGER NULL);');
			}
		}
		elseif (str_starts_with(KEY_VALUE_CACHE_ENGINE, 'redis:')) {
			$server = substr(KEY_VALUE_CACHE_ENGINE, strlen('redis:'));
			$this->redis = new RedisClient($server);
		}
		else {
			throw new \LogicException('Invalid KEY_VALUE_CACHE_ENGINE value: ' . KEY_VALUE_CACHE_ENGINE);
		}
	}

	public function set(string $key, ?string $value, ?int $expires_in = null): void
	{
		if ($this->db) {
			$expiry = null;

			if ($expires_in) {
				$expiry = time() + $expires_in;
			}

			$this->write_statement ??= $this->db->prepare('REPLACE INTO store (key, value, expiry) VALUES (?, ?, ?);');
			$st = $this->write_statement;
			$st->clear();
			$st->reset();
			$st->bindValue(1, $key);
			$st->bindValue(2, $value);
			$st->bindValue(3, $expiry);
			$st->execute();
		}
		else {
			$this->redis->set($key, $value, 'EX', $expires_in);
		}
	}

	public function begin(): void
	{
		if ($this->transaction++ > 0) {
			return;
		}

		if ($this->db) {
			$this->db->exec('BEGIN;');
		}
		else {
			$this->redis->multi();
		}
	}

	public function commit(): void
	{
		if (--$this->transaction > 0) {
			return;
		}

		if ($this->db) {
			$this->db->exec('END;');
		}
		else {
			$this->redis->exec();
		}
	}

	public function get(string $key): mixed
	{
		if ($this->db) {
			$value = $this->db->querySingle(sprintf('SELECT value FROM store WHERE key = \'%s\' AND expiry > %d;',  $this->db->escapeString($key), time()));
			return $value === false ? null : $value;
		}
		else {
			$value = $this->redis->get($key);
		}

		return $value;
	}

	public function exists(string $key): bool
	{
		if ($this->db) {
			$value = $this->db->querySingle(sprintf('SELECT 1 FROM store WHERE key = \'%s\' AND expiry > %d;',  $this->db->escapeString($key), time()));
		}
		else {
			$value = $this->redis->exists($key);
		}

		return (bool) $value;
	}

	public function delete(string $key): void
	{
		if ($this->db) {
			$value = $this->db->exec(sprintf('DELETE FROM store WHERE key = \'%s\';',  $this->db->escapeString($key)));
		}
		else {
			$this->redis->del($key);
		}
	}
}
