<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Files\Storage;

use stdClass;

/**
 * Backup and restore the database in separate files.
 *
 * Every backup downloaded by the user (with a browser or via API)
 * will have a SHA1 hash appended at the end of the SQLite file.
 *
 * SQLite doesn't bother, and when the file is changed with another program,
 * the hash is usually removed.
 *
 * When restoring a database uploaded by the user, the hash is checked
 * (unless ALLOW_MODIFIED_IMPORT is true), and the restore will be aborted
 * if the hash doesn't match with the contents of the file.
 *
 * This is *NOT* a security feature. It's only there to deter users from
 * tinkering with the database and breaking the software.
 */
class Backup
{
	const NEED_UPGRADE = 0x01 << 2;
	const NOT_AN_ADMIN = 0x01 << 3;
	const CHANGED_USER = 0x01 << 4;

	const INTEGRITY_FAIL = 41;
	const NOT_A_DB = 42;
	const NO_APP_ID = 43;

	const PREFIX = 'pko-';
	const SUFFIX = '.sqlite';
	const AUTO_PREFIX = 'auto-';
	const UPGRADE_PREFIX = 'before-upgrade-';
	const RESET_PREFIX = 'before-reset-';
	const RESTORE_PREFIX = 'before-restore-';

	static protected function isFilenameValid(string $name): bool
	{
		if (false !== strpos($name, '..')) {
			return false;
		}

		if (substr($name, - strlen(self::SUFFIX)) !== self::SUFFIX) {
			return false;
		}

		if ($name === basename(DB_FILE)) {
			return false;
		}

		if (0 !== strpos($name, self::PREFIX)) {
			return false;
		}

		return (bool) preg_match('!^[a-z0-9_-]+(?:\.[a-z0-9_-]+)*$!i', $name);
	}

	static protected function validateFileName(string $name): void
	{
		if (!self::isFilenameValid($name)) {
			throw new \InvalidArgumentException('Invalid file name');
		}
	}

	static protected function getReadableName(string $file): string
	{
		$name = substr($file, strlen(self::PREFIX), - strlen(self::SUFFIX));

		if (0 === strpos($name, self::AUTO_PREFIX)) {
			$auto = (int) substr($name, strlen(self::AUTO_PREFIX));
			$name = sprintf('Automatique n°%d', $auto);
		}
		elseif (0 === strpos($name, self::UPGRADE_PREFIX)) {
			$name = sprintf('Avant mise à jour %s', substr($name, strlen(self::UPGRADE_PREFIX)));
		}
		elseif (0 === strpos($name, self::RESET_PREFIX)) {
			$name = 'Avant remise à zéro';
		}
		elseif (preg_match('/^\d{4}-/', $name)) {
			$name = 'Sauvegarde manuelle';
		}

		return $name;
	}

	static protected function isAuto(string $name): bool
	{
		return 0 === strpos($name, self::PREFIX . self::AUTO_PREFIX);
	}

	static public function getDBDetails(string $path): stdClass
	{
		$file = Utils::basename($path);
		self::validateFileName($file);
		$auto = null;
		$name = self::getReadableName($file);

		$error = null;
		$version = null;
		$db = null;

		try {
			$db = new \SQLite3($path, \SQLITE3_OPEN_READONLY);
			$version = DB::getVersion($db);
			$db->close();
		}
		catch (\LogicException $e) {
			$error = $e->getMessage();
		}
		catch (\Exception $e) {
			$error = $db ? $db->lastErrorMsg() : $e->getMessage();
		}

		if ($version && version_compare($version, paheko_version(), '>')) {
			$error = 'Cette version est trop récente';
		}

		$can_restore = $version && !$error ? version_compare($version, Upgrade::MIN_REQUIRED_VERSION, '>=') : false;

		return (object) [
			'filename'    => $file,
			'date'        => filemtime($path),
			'name'        => $name,
			'version'     => $version,
			'can_restore' => $can_restore,
			'auto'        => self::isAuto($file),
			'size'        => filesize($path),
			'error'       => $error,
		];
	}

	/**
	 * Returns the list of SQLite backups
	 * @param  boolean $auto If true only automatic backups will be returned
	 * @return array
	 */
	static public function list(bool $auto_only = false): array
	{
		$ext = $auto_only ? 'auto\.\d+\.sqlite' : 'sqlite';

		$out = [];
		$dir = dir(BACKUPS_ROOT);

		while ($file = $dir->read()) {
			// Keep only backup files
			if ($file[0] === '.'
				|| !self::isFilenameValid($file)
				|| !is_file(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $file)) {
				continue;
			}

			// Skip non-auto files
			if ($auto_only && 0 !== strpos($file, self::PREFIX . self::AUTO_PREFIX)) {
				continue;
			}

			$out[$file] = self::getDBDetails(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $file);
		}

		$dir->close();

		// Reverse date order
		uasort($out, function ($a, $b) {
			return $a->date > $b->date ? -1 : 1;
		});

		return $out;
	}

	static public function create(?string $name = null): string
	{
		Utils::safe_mkdir(BACKUPS_ROOT, fileperms(DATA_ROOT), true);
		$name ??= date('Y-m-d-His');
		$name = self::PREFIX . $name . self::SUFFIX;

		self::make(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name);

		return $name;
	}

	static public function createBeforeUpgrade(string $version): string
	{
		return self::create(self::UPGRADE_PREFIX . $version);
	}

	static public function createBeforeReset(): string
	{
		return self::create(self::RESET_PREFIX . date('Y-m-d-His'));
	}

	/**
	 * Actually create a backup
	 * @param string|null $destination If NULL, then will just create a temporary file and return its path
	 */
	static public function make(?string $destination): string
	{
		// Acquire lock
		$version = \SQLite3::version();
		$db = DB::getInstance();

		// Use a temporary file so that if BACKUPS_ROOT is on NFS,
		// we don't have issues with SQLite writing over NFS
		$tmp = tempnam(CACHE_ROOT, 'sqlite-backup-');

		// use VACUUM INTO when SQLite 3.27+ is available
		if ($version['versionNumber'] >= 3027000) {
			// We need to allow ATTACH here, as VACUUM INTO is using ATTACH,
			// which is restricted for security reasons, so we disable the authorizer
			DB::toggleAuthorizer($db, false);

			$db->exec(sprintf('VACUUM INTO %s;', $db->quote($tmp)));

			DB::toggleAuthorizer($db, true);
			$dest_db = new \SQLite3($tmp);
		}
		else {
			// use ::backup since PHP 7.4.0+
			// https://www.php.net/manual/en/sqlite3.backup.php
			$dest_db = new \SQLite3($tmp);
			$dest_db->createCollation('U_NOCASE', [Utils::class, 'unicodeCaseComparison']);

			$db->backup($dest_db);
		}

		// Make sure the backup file has DELETE journal mode so the WAL file is squashed
		$dest_db->exec('PRAGMA journal_mode = DELETE;');
		$dest_db->exec('VACUUM;');
		$dest_db->close();

		if (null !== $destination) {
			rename($tmp, $destination);
		}

		return $destination ?? $tmp;
	}

	/**
	 * Rotate automatic backups
	 * association.auto.2.sqlite -> association.auto.3.sqlite
	 * association.auto.1.sqlite -> association.auto.2.sqlite
	 * etc.
	 */
	static public function rotate(): void
	{
		$config = Config::getInstance();
		$nb = $config->get('backup_limit');

		$list = self::list(true);

		// Sort backups from oldest to newest
		usort($list, function ($a, $b) {
			return $a->auto > $b->auto ? -1 : 1;
		});

		// Delete oldest backups + 1 as we are about to create a new one
		$delete = count($list) - ($nb - 1);

		for ($i = 0; $i < $delete; $i++) {
			$backup = array_shift($list);
			self::remove($backup->filename);
		}

		$i = count($list) + 1;

		// Rotate old backups
		foreach ($list as $file) {
			$old = BACKUPS_ROOT . DIRECTORY_SEPARATOR . $file->filename;
			$new = BACKUPS_ROOT . DIRECTORY_SEPARATOR . self::AUTO_PREFIX . $i-- . self::SUFFIX;

			if ($old !== $new) {
				rename($old, $new);
			}
		}
	}

	/**
	 * Create a new automatic backup, if required
	 */
	static public function auto(): void
	{
		$config = Config::getInstance();

		// Pas besoin d'aller plus loin si on ne fait pas de sauvegarde auto
		if ($config->get('backup_frequency') == 0 || $config->get('backup_limit') == 0) {
			return;
		}

		$list = self::list(true);

		if (count($list)) {
			$last = current($list)->date;
		}
		else {
			$last = false;
		}

		// Test de la date de création de la dernière sauvegarde
		if ($last >= (time() - ($config->get('backup_frequency') * 3600 * 24))) {
			return;
		}

		// Si pas de modif depuis la dernière sauvegarde, ça sert à rien d'en faire
		if ($last >= filemtime(DB_FILE)) {
			return;
		}

		self::rotate();
		self::create(true);
	}

	/**
	 * Delete a local backup
	 */
	static public function remove(string $name): void
	{
		self::validateFileName($name);
		Utils::safe_unlink(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name);
	}

	/**
	 * Download a backup file in the browser.
	 * @param $name If NULL, then the current database will be dumped.
	 */
	static public function dump(?string $name = null): void
	{
		$config = Config::getInstance();
		$tmp_file = null;

		if (null === $name) {
			$download_name = sprintf('%s - Sauvegarde données - %s.sqlite', $config->get('org_name'), date('Y-m-d'));

			$file = self::make();
		}
		else {
			self::validateFileName($name);

			$file = BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name;
			$download_name = sprintf('%s - %s', $config->get('org_name'), self::getReadableName($name));

			if (!file_exists($file)) {
				throw new UserException('Le fichier fourni n\'existe pas.');
			}
		}

		$download_name .= self::SUFFIX;
		$hash = sha1_file($file);
		$hash_length = strlen($hash);

		header('Content-type: application/octet-stream');
		header(sprintf('Content-Disposition: attachment; filename="%s"', $download_name));
		header(sprintf('Content-Length: %d', filesize($file) + $hash_length));

		readfile($file);

		// Append integrity hash
		echo $hash;

		if (null !== $name) {
			Utils::safe_unlink($file);
		}
	}

	/**
	 * Restore from a local backup
	 */
	static public function restoreFromLocal(string $name, ?Session $session): int
	{
		self::validateFileName($name);

		if (!file_exists(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name)) {
			throw new UserException('Le fichier fourni n\'existe pas.');
		}

		return self::restore(BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name, $session, false);
	}

	/**
	 * Restore from an uploaded file
	 * @param  array   $file    Array provided by $_FILES
	 * @param  Session   $session
	 * @param  boolean $check_integrity Validate checksum before restore
	 * @return int
	 */
	static public function restoreFromUpload(array $file, ?Session $session, bool $check_integrity = true): int
	{
		if (empty($file['size']) || empty($file['tmp_name']) || !empty($file['error'])) {
			throw new UserException('Le fichier n\'a pas été correctement envoyé. Essayer de le renvoyer à nouveau.');
		}

		if ($check_integrity) {
			$integrity = self::checkIntegrity($file['tmp_name']);

			if ($integrity === null) {
				throw new UserException('Le fichier fourni n\'est pas une base de donnée SQLite3.', self::NOT_A_DB);
			}
			elseif ($integrity === false) {
				throw new UserException('Le fichier fourni a été modifié par un programme externe.', self::INTEGRITY_FAIL);
			}
		}

		$r = self::restore($file['tmp_name'], $session, true);

		if ($r) {
			Utils::safe_unlink($file['tmp_name']);
		}

		return $r;
	}

	/**
	 * Verify if a file is a valid SQLite3 backup and its contents match the appended SHA1 hash
	 * @return null|bool NULL if file is not a SQLite3 database. FALSE if the hash does not match.
	 */
	static protected function checkIntegrity(string $file_path, bool $remove_hash = true): ?bool
	{
		$size = filesize($file_path);
		$fp = fopen($file_path, 'r+');

		$header = fread($fp, 16);

		// Vérifie que le fichier est bien une base SQLite3
		if ($header !== "SQLite format 3\000") {
			fclose($fp);
			return null;
		}

		fseek($fp, -40, SEEK_END);

		$hash = fread($fp, 40);

		// Ne ressemble pas à un hash sha1
		if (!preg_match('/[a-f0-9]{40}/', $hash)) {
			fclose($fp);
			return false;
		}

		$max = $size - 40;

		// Suppression du hash
		if ($remove_hash) {
			ftruncate($fp, $max);
		}

		fclose($fp);

		$file_hash = sha1_file($file_path);

		// Vérification du hash
		return ($file_hash === $hash);
	}

	/**
	 * Restore a database
	 * @return integer:
	 * - 1 if everything is OK
	 * - & self::NEED_UPGRADE if database version is older and requires an upgrade
	 * - & self::NOT_AN_ADMIN if in the restored database the logged user ID passed is not a config admin
	 */
	static protected function restore(string $file, ?Session $session = null, bool $check_foreign_keys = false): int
	{
		$return = 1;

		// First try to open database
		try {
			$db = new \SQLite3($file, \SQLITE3_OPEN_READONLY);
		}
		catch (\Exception $e) {
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage(), self::NOT_A_DB);
		}


		// see https://www.sqlite.org/security.html
		$db->exec('PRAGMA cell_size_check = ON;');
		$db->exec('PRAGMA mmap_size = 0;');

		if ($db->version()['versionNumber'] >= 3041000) {
			$db->exec('PRAGMA trusted_schema = OFF;');
		}

		DB::toggleAuthorizer($db, true);
		DB::registerCustomFunctions($db);

		try {
			// Now let's check integrity
			$check = $db->querySingle('PRAGMA integrity_check;', false);
		}
		catch (\Exception $e) {
			// SQLite can throw an error like: "file is encrypted or is not a db"
			throw new UserException('Le fichier fourni n\'est pas une base de données valide. ' .
				'Message d\'erreur de SQLite : ' . $e->getMessage(), self::NOT_A_DB);
		}

		if (strtolower(trim($check)) != 'ok') {
			throw new UserException('Le fichier fourni est corrompu. Erreur SQLite : ' . $check);
		}

		if ($check_foreign_keys) {
			$check = $db->querySingle('PRAGMA foreign_key_check;');

			if ($check) {
				throw new UserException('Le fichier fourni est corrompu. Certaines clés étrangères référencent des lignes qui n\'existent pas.');
			}
		}

		// We can't really check if the schema is exactly the one we are expecting
		// as we allow to restore from old versions, and that would mean storing
		// all possible old schemas. But we can still see if it looks like a schema
		// coming from Paheko by looking for the config table.
		$table = $db->querySingle('SELECT 1 FROM sqlite_master WHERE type=\'table\' AND tbl_name=\'config\';');

		if (!$table) {
			throw new UserException('Le fichier fourni ne semble pas contenir de données liées à Paheko.');
		}

		$version = DB::getVersion($db);

		// We can't possibly handle any old version
		if (version_compare($version, Upgrade::MIN_REQUIRED_VERSION, '<')) {
			throw new UserException(sprintf('Ce fichier a été créé avec une version trop ancienne (%s), il n\'est pas possible de le restaurer.', $version));
		}

		if ($version && version_compare($version, paheko_version(), '>')) {
			throw new UserException(sprintf('Ce fichier provient d\'une version plus récente de Paheko (%s), que celle qui est installée (%s), il n\'est pas possible de le restaurer.', $version, paheko_version()));
		}

		// Check for AppID
		$appid = $db->querySingle('PRAGMA application_id;', false);

		if ($appid !== DB::APPID) {
			throw new UserException('Ce fichier n\'est pas une sauvegarde Paheko (application_id ne correspond pas).', self::NO_APP_ID);
		}

		// module and plugins names should never contain a slash, if it does it might be a malicious database
		$malicious_plugins = $db->querySingle('SELECT 1 FROM plugins WHERE name LIKE \'%/%\' OR name LIKE \'%\\%\';', false);
		$malicious_modules = $db->querySingle('SELECT 1 FROM modules WHERE name LIKE \'%/%\' OR name LIKE \'%\\%\';', false);

		// File names or paths must never try to do path traversal, or it might be malicious
		$malicious_modules_templates = $db->querySingle('SELECT 1 FROM modules_templates WHERE name LIKE \'%..%\';', false);
		$malicious_files = $db->querySingle('SELECT 1 FROM files WHERE name LIKE \'%../%\' OR name LIKE \'%..\\%\'
			OR path LIKE \'%../%\' OR path LIKE \'%..\\%\'
			OR parent LIKE \'%../%\' OR parent LIKE \'%..\\%\';', false);

		if ($malicious_plugins || $malicious_modules || $malicious_modules_templates || $malicious_files) {
			throw new UserException('Malicious database detected (path traversal attempt)');
		}

		// Try to handle case where the admin performing the restore is no longer an admin in the restored database
		if ($session && $session->isLogged(false)) {
			$sql = 'SELECT 1 FROM users_categories WHERE id = (SELECT id_category FROM users WHERE id = %d) AND perm_connect >= %d AND perm_config >= %d';

			$sql = sprintf($sql, $session->getUser()->id, Session::ACCESS_READ, Session::ACCESS_ADMIN);
			$is_still_admin = $db->querySingle($sql);

			if (!$is_still_admin) {
				$return |= self::NOT_AN_ADMIN;
			}
		}

		$db->close();

		$backup = BACKUPS_ROOT . DIRECTORY_SEPARATOR . self::PREFIX . self::RESTORE_PREFIX . date('Ymd-His') . self::SUFFIX;

		DB::getInstance()->close();

		if (!rename(DB_FILE, $backup)) {
			throw new \RuntimeException('Unable to backup current DB file.');
		}

		if (!copy($file, DB_FILE)) {
			rename($backup, DB_FILE);
			throw new \RuntimeException('Unable to copy backup DB to main location.');
		}

		unlink($backup);

		// Force all categories to be able to manage users
		if ($return & self::NOT_AN_ADMIN) {
			$db = DB::getInstance();
			$db->exec(sprintf('UPDATE users_categories SET perm_config = %d, perm_connect = %d;', Session::ACCESS_ADMIN, Session::ACCESS_READ));
		}

		if ($version != paheko_version()) {
			$return |= self::NEED_UPGRADE;
		}
		else {
			// Check and upgrade plugins, if a software upgrade is necessary, plugins will be upgraded after the upgrade
			Plugins::upgradeAllIfRequired();

			// Re-sync files cache with storage, if necessary
			Storage::sync();
		}

		// Make sure we delete files stored in database if we don't store files in database
		if (FILE_STORAGE_BACKEND !== 'SQLite') {
			Storage::truncate('SQLite', null);
		}

		$name = Utils::basename($file);
		$name = self::getReadableName($name);
		Log::add(Log::MESSAGE, ['message' => 'Sauvegarde restaurée : ' . $name], $session::getUserId());

		return $return;
	}

	/**
	 * returns current database size in bytes
	 */
	static public function getDBSize(bool $signed = false): int
	{
		clearstatcache(true, DB_FILE);
		return filesize(DB_FILE) + ($signed ? 40 : 0);
	}

	/**
	 * Returns size of all backups
	 */
	static public function getAllBackupsTotalSize(): int
	{
		$size = 0;

		foreach (glob(BACKUPS_ROOT . '/*' . self::SUFFIX) as $f) {
			if ($f === basename(DB_FILE)) {
				continue;
			}

			$size += filesize($f);
		}

		return $size;
	}
}
