<?php

namespace Paheko\Entities\Files;

use Paheko\Config;
use Paheko\Files\Files;
use KD2\DB\EntityManager as EM;

use const Paheko\{FILE_VERSIONING_MAX_SIZE, FILE_VERSIONING_POLICY};

trait FileVersionsTrait
{
	protected function createVersionName(int $v, ?int $ts = null, string $name = '')
	{
		$ts ??= time();

		if ('' !== $name) {
			$name = str_replace(self::FORBIDDEN_CHARACTERS, '', $name);
			$name = substr($name, 0, 200);
		}

		// 20 characters for version number + timestamp (10 characters, will last until year 2286)
		return sprintf('%08d.%d.%s', $v, $ts, $name);
	}

	protected function createVersion()
	{
		// Don't version empty content
		if (0 === $this->getModifiedProperty('size')) {
			return;
		}

		if (!in_array($this->context(), self::VERSIONED_CONTEXTS)) {
			return;
		}

		$config = Config::getInstance();
		$policy = FILE_VERSIONING_POLICY ?? $config->file_versioning_policy;

		// Versioning is disabled
		if ('none' === $policy) {
			return;
		}

		$max_size = FILE_VERSIONING_MAX_SIZE ?? $config->file_versioning_max_size;

		// Don't version large files
		if ($this->size > $max_size*1024*1024) {
			return;
		}

		$last = EM::getInstance(File::class)->col('SELECT name FROM @TABLE WHERE parent = ? ORDER BY name DESC LIMIT 1;',
			self::CONTEXT_VERSIONS . '/' . $this->path);

		if ($last) {
			$v = (int) strtok($last, '.');
			strtok(false);
			$v++;
		}
		else {
			$v = 1;
		}

		if ($ts = $this->getModifiedProperty('modified')) {
			$ts = new \DateTime($ts);
		}
		else {
			$ts = $this->modified;
		}

		// file pattern: versions/ORIGINAL_PATH/000001.TIMESTAMP.NAME
		$name = $this->createVersionName($v, $ts->getTimestamp());
		$this->copy(sprintf('%s/%s/%s', self::CONTEXT_VERSIONS, $this->path, $name));
	}

	/**
	 * Delete versions linked to this file
	 */
	public function deleteVersions(): bool
	{
		$parent = Files::get(self::CONTEXT_VERSIONS . '/' . $this->path);

		if ($parent) {
			return $parent->delete();
		}

		return true;
	}

	public function pruneVersions(): void
	{
		$now = time();

		$config = Config::getInstance();
		$policy = FILE_VERSIONING_POLICY ?? $config->file_versioning_policy;

		// Versioning is disabled, but keep old versions
		if ('none' === $policy) {
			return;
		}

		$versions_policy = Config::VERSIONING_POLICIES[$policy]['intervals'];
		ksort($versions_policy);

		// last step
		$max_step = $versions_policy[-1] ?? null;
		unset($versions_policy[-1]);

		$versions = $this->listVersions();
		// Sort by timestamp, not by version
		uasort($versions, fn($a, $b) => $a->timestamp == $b->timestamp ? 0 : ($a->timestamp > $b->timestamp ? -1 : 1));

		$delete = [];
		reset($versions_policy);
		$step = current($versions_policy);
		$last_timestamp = null;

		foreach ($versions as $v) {
			if ($v->name) {
				continue;
			}

			$version_diff = $now - $v->timestamp;

			while ($version_diff > key($versions_policy) && $step !== false) {
				// Skip to next interval by fetching next step
				$step = next($versions_policy);
				$last_timestamp = null;
			}

			// Use max step value if we have reached the max interval
			if (false === $step) {
				$step = $max_step;
			}

			// Keep named versions, but make them count for next step
			if ($v->name) {
				$keep = true;
			}
			elseif (!$last_timestamp) {
				$keep = true;
			}
			elseif (($last_timestamp - $v->timestamp) > $step) {
				$keep = true;
			}
			else {
				// This version interval is already filled, delete
				$delete[] = $v;
				$keep = false;
			}

			if ($keep) {
				// Keep this version
				$last_timestamp = $v->timestamp;
			}
		}

		unset($v);

		foreach ($delete as $v) {
			$v->file->delete();
		}
	}

	public function getVersion(int $v): ?self
	{
		$v = EM::getInstance(File::class)->one(
			'SELECT * FROM @TABLE WHERE parent = ? AND name LIKE ? LIMIT 1;',
			self::CONTEXT_VERSIONS . '/' . $this->path,
			sprintf('%08d.%%', $v)
		);

		if (!$v) {
			throw new \InvalidArgumentException('Version not found');
		}

		return $v;
	}

	public function getVersionMetadata(File $v): \stdClass
	{
		$out = (object) [
			'version'   => (int) strtok($v->name, '.'),
			'timestamp' => (int) strtok('.'),
			'name'      => strtok(false),
			'size'      => $v->size,
			'file'      => $v,
		];

		$out->date = \DateTime::createFromFormat('U', $out->timestamp);

		// Set to local timezone
		$out->date->setTimeZone((new \DateTime)->getTimeZone());

		return $out;
	}

	public function renameVersion(int $v, string $name): void
	{
		$v = $this->getVersion($v);

		$meta = $this->getVersionMetadata($v);
		$new_name = $this->createVersionName($meta->version, $meta->timestamp, trim($name));

		$v->changeFileName($new_name);
	}

	public function restoreVersion(int $v): void
	{
		$v = $this->getVersion($v);

		$this->createVersion();
		$v->rename($this->path);
	}

	public function deleteVersion(int $v): void
	{
		$v = $this->getVersion($v);
		$v->delete();
	}

	public function downloadVersion(int $v): void
	{
		$v = $this->getVersion($v);
		$v->serve($this->name);
	}

	public function listVersions(): array
	{
		$out = [];
		$i = 1;

		foreach (Files::list(self::CONTEXT_VERSIONS . '/' . $this->path) as $v) {
			$out[$v->name] = $this->getVersionMetadata($v);
		}

		krsort($out);
		return $out;
	}

	public function getVersionsDirectory(): ?File
	{
		if ($this->context() === self::CONTEXT_VERSIONS) {
			return null;
		}

		return Files::get(self::CONTEXT_VERSIONS . '/' . $this->path);
	}
}
