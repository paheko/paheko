<?php

namespace Paheko\Files\Storage;

use Paheko\Entities\Files\File;

class StorageException extends \RuntimeException {}

/**
 * Storage backends must implement this interface
 *
 * In Paheko, storage backends actually store the file contents,
 * while the file metadata is stored inside the 'files' table of
 * the database.
 *
 * A backend storage MUST be able to store:
 * - the file contents
 * - the file modification date
 * - the file name and path
 *
 * This is because the 'files' table is just a cache, it can corrupt
 * or need to be refreshed after a database update.
 *
 * This means that if your storage backend does NOT store files in a file system,
 * you may have to store these metadata in another place.
 *
 * Directories: storage backends should not worry about directories,
 * directories are automatically created in cache when required.
 * EXCEPT in the case of delete(), a storage backend can speed things up
 * by deleting a folder directly, if it can.
 */
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
	 * Change the file modified time to a specific date
	 */
	static public function touch(File $file, \DateTime $date): void;

	/**
	 * Rename/move a file
	 *
	 * If a file of the same name exists, it should be overwritten.
	 */
	static public function rename(File $file, string $new_path): bool;

	/**
	 * Delete a file or directory (whether it was placed in trash before or not)
     *
	 * Note that if the storage backend is not able to delete all files and folders inside
	 * a folder, it should return false here when trying to delete a directory.
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

	/**
	 * List files *from* storage
	 *
	 * This is called when the database is restored,
	 * or if the admin runs the 'scan_files' script.
	 *
	 * This method MUST return an array containing unsaved File objects.
	 * This method MUST NOT rehash every file encountered while listing.
	 *
	 * The sync algorithm WILL rehash() and save() if the file has been
	 * changed or doesn't exist in cache.
	 *
	 * Empty directories in storage shouldn't lead to creating an empty
	 * directory in cache.
	 *
	 * @var string|null $path Contains the local path, eg. 'documents/dir'
	 * or the '.Trash' string to sync trashed files
	 */
	static public function listFiles(?string $path = null): array;

	/**
	 * Perform storage-specific clean-up tasks
	 */
	static public function cleanup(): void;
}
