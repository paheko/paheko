<?php

namespace Paheko\Files\Storage;

use Paheko\Entities\Files\File;

class StorageException extends \RuntimeException {}

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
	 * Stores a file from a file pointer
	 */
	static public function storePointer(File $file, $pointer): bool;

	/**
	 * Should return full local file access path.
	 * If storage backend cannot store the file locally, return NULL.
	 * In that case a subsequent call to getReadOnlyPointer() will be done.
	 */
	static public function getLocalFilePath(File $file): ?string;

	/**
	 * Returns a read-only file pointer (resource) to the file contents
	 * If the storage backedn cannot provide a pointer, return NULL.
	 * In that case a subsequent call to getLocalFilePath() will be done.
	 * @return null|resource
	 */
	static public function getReadOnlyPointer(File $file);

	/**
	 * Delete a file
	 */
	static public function delete(File $file): bool;

	/**
	 * Return total disk space
	 * This will only be called if FILE_STORAGE_QUOTA constant is null
	 */
	static public function getQuota(): float;

	/**
	 * Return available free disk space
	 * This will only be called if FILE_STORAGE_QUOTA constant is null
	 */
	static public function getRemainingQuota(): float;

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
	 * Return TRUE if storage is locked against changes
	 */
	static public function isLocked(): bool;
}
