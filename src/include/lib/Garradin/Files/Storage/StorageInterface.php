<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

interface StorageInterface
{
	static public function store(File $file, ?string $path, ?string $content): bool;

	/**
	 * List files contained in a path, this must return an array of File instances
	 * If this storage backend wants to leave the directory handling to Garradin, just return NULL.
	 * @param  string $path
	 * @return array[File...]
	 */
	static public function list(string $path): ?array;

	/**
	 * Should return full local file access path.
	 * If storage backend cannot store the file locally, return NULL.
	 * In that case a subsequent call to fetch() will be done.
	 */
	static public function getPath(File $file): ?string;

	static public function display(File $file): void;

	static public function fetch(File $file): string;

	static public function delete(File $file): bool;

	static public function move(File $old_file, File $new_file): bool;

	static public function getTotalSize(): int;

	static public function getQuota(): int;

	static public function cleanup(): void;
}