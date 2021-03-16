<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

interface StorageInterface
{
	/**
	 * Configures the storage backend for subsequent calls
	 */
	static public function configure(?string $config): void;

	/**
	 * Stores a file in this backend from a local path
	 */
	static public function storePath(File $file, string $source_path): bool;

	/**
	 * Stores a file from a content binary string
	 */
	static public function storeContent(File $file, string $source_content): bool;

	/**
	 * Create an empty directory
	 */
	static public function mkdir(File $file): bool;

	/**
	 * Should return full local file access path.
	 * If storage backend cannot store the file locally, return NULL.
	 * In that case a subsequent call to fetch() will be done.
	 */
	static public function getFullPath(File $file): ?string;

	/**
	 * Returns the binary of a content to php://output
	 */
	static public function display(File $file): void;

	/**
	 * Returns the binary content of a file
	 */
	static public function fetch(File $file): string;

	/**
	 * Delete a file
	 */
	static public function delete(File $file): bool;

	/**
	 * Gets modified timestamp
	 */
	static public function modified(File $file): ?int;

	/**
	 * Return TRUE if file exists
	 */
	static public function exists(string $path): bool;

	/**
	 * Moves a file to a new path, when its name or path has changed
	 */
	static public function move(File $file, string $new_path): bool;

	/**
	 * Return total size of used space by files stored in this backed
	 */
	static public function getTotalSize(): int;

	/**
	 * Return total disk space
	 * This will only be called if FILE_STORAGE_QUOTA constant is null
	 */
	static public function getQuota(): int;

	/**
	 * Return available free disk space
	 * This will only be called if FILE_STORAGE_QUOTA constant is null
	 */
	static public function getRemainingQuota(): int;

	/**
	 * Delete all stored content in this backend
	 */
	static public function truncate(): void;

	/**
	 * Lock storage backend against changes
	 * This is used when migrating from one storage to another
	 */
	static public function lock(): void;

	/**
	 * Unlock storage backend against changes
	 */
	static public function unlock(): void;

	/**
	 * Throw a \RuntimeException if the lock is active
	 */
	static public function checkLock(): void;

	/**
	 * Update metadata in database from local directory
	 * This is called before listing a directory
	 *
	 * The backend must list files for this path and update/add/delete them
	 * using File entities.
	 *
	 * @param  string $path Parent path
	 * @return void
	 */
	static public function sync(?string $path): void;

	/**
	 * Update metadata of a file if needed, before getting it
	 *
	 * This is called before getting any metadata of a file
	 *
	 * @param  File $file
	 * @return File modified File object, or NULL if the file no longer exists
	 */
	static public function update(File $file): ?File;
}
