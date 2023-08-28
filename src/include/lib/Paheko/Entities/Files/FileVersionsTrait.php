<?php

namespace Paheko\Entities\Files;

use Paheko\Files\Files;
use KD2\DB\EntityManager as EM;

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
		if (!in_array($this->context(), self::VERSIONED_CONTEXTS)) {
			return;
		}

		// Don't version large files (> 50MB)
		if ($this->size > 1024*1024*50) {
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

		// FIXME: get policy from config
		$max_versions_per_interval = [
			//ends_after => step (interval size)

			// First 10 minutes, one version every 1 minute
			600 => 60,

			// Next hour, one version every 10 minutes
			3600 => 600,

			// Next 24h, one version every hour
			3600*24 => 3600,

			// Next 2 months, one version per week
			3600*24*60 => 3600*24*7,

		];

		// Keep one version each trimester after first 2 months
		$max_step = 3600*24*30;

		$last_step = null;
		$last_timestamp = null;

		foreach ($this->listVersions() as $v) {
			if ($v->name) {
				continue;
			}

			$version_diff = $now - $v->timestamp;
			$interval = null;
			$step = null;

			foreach ($max_versions_per_interval as $_interval => $_step) {
				// Skip to next interval
				if ($version_diff > $_interval) {
					continue;
				}

				$step = $_step;
				$interval = $_interval;
				break;
			}

			$step ??= $max_step;

			if ($last_step !== $step) {
				$last_timestamp = null;
				$last_step = $step;
			}

			// This version interval is already filled, delete, unless it has a name
			if (!$v->name
				&& $last_timestamp
				&& ($last_timestamp - $v->timestamp) < $step) {
				$v->file->delete();
				continue;
			}

			// Keep this version
			$last_timestamp = $v->timestamp;
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
