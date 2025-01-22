<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Files\Storage;

use stdClass;

class Backup
{
	const NEED_UPGRADE = 0x01 << 2;
	const NOT_AN_ADMIN = 0x01 << 3;
	const CHANGED_USER = 0x01 << 4;

	const INTEGRITY_FAIL = 41;
	const NOT_A_DB = 42;
	const NO_APP_ID = 43;

	static public function getDBDetails(string $path): stdClass
	{
		$file = Utils::basename($path);
		$name = preg_replace('/^association\.(.*)\.sqlite$/', '$1', $file);
		$auto = null;

		if (substr($name, 0, 5) == 'auto.') {
			$auto = (int) substr($name, 5);
			$name = sprintf('Automatique n°%d', $auto);
		}
		elseif (0 === strpos($name, 'pre-upgrade-')) {
			$name = sprintf('Avant mise à jour %s', substr($name, strlen('pre-upgrade-')));
		}
		elseif (preg_match('/^\d{4}-/', $name)) {
			$name = 'Sauvegarde manuelle';
		}
		else {
			$name = str_replace('.sqlite', '', $name);
		}

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
			'name'        => $name != $file ? $name : null,
			'version'     => $version,
			'can_restore' => $can_restore,
			'auto'        => $auto,
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
		$dir = dir(DATA_ROOT);

		while ($file = $dir->read()) {
			// Keep only backup files
			if ($file[0] == '.' || !is_file(DATA_ROOT . '/' . $file)
				|| !preg_match('![\w\d._-]+\.' . $ext . '$!i', $file) && $file != basename(DB_FILE)) {
				continue;
			}

			if ($file === basename(DB_FILE)) {
				continue;
			}

			if (0 !== strpos($file, 'association.')) {
				continue;
			}

			// Skip non-auto files
			if ($auto_only && 0 !== strpos($file, 'association.auto.')) {
				continue;
			}

			$out[$file] = self::getDBDetails(DATA_ROOT . '/' . $file);
		}

		$dir->close();

		// Reverse date order
		uasort($out, function ($a, $b) {
			return $a->date > $b->date ? -1 : 1;
		});

		return $out;
	}

	/**
	 * Create a new backup
	 * @param  boolean $auto If TRUE, the file name will be based on automatic backups,
	 * if FALSE a file name containing the date will be used (manual backup).
	 * @return string Backup file name
	 */
	static public function create(bool $auto = false, ?string $name = null): string
	{
		$suffix = $name ?? ($auto ? 'auto.1' : date('Y-m-d-His'));

		$backup = str_replace('.sqlite', sprintf('.%s.sqlite', $suffix), DB_FILE);

		self::make($backup);

		return basename($backup);
	}

	/**
	 * Actually create a backup
	 */
	static public function make(string $dest): void
	{
		// Acquire lock
		$version = \SQLite3::version();
		$db = DB::getInstance();

		Utils::safe_unlink($dest);

		if ($version['versionNumber'] >= 3027000) {
			// We need to allow ATTACH here, as VACUUM INTO is using ATTACH,
			// so we disable the authorizer
			DB::toggleAuthorizer($db, false);

			// use VACUUM INTO instead when SQLite 3.27+ is available
			$db->exec(sprintf('VACUUM INTO %s;', $db->quote($dest)));

			DB::toggleAuthorizer($db, true);
		}
		else {
			// use ::backup since PHP 7.4.0+
			// https://www.php.net/manual/en/sqlite3.backup.php
			$dest_db = new \SQLite3($dest);
			$dest_db->createCollation('U_NOCASE', [Utils::class, 'unicodeCaseComparison']);

			$db->backup($dest_db);
			$dest_db->exec('PRAGMA journal_mode = DELETE;');
			$dest_db->exec('VACUUM;');
			$db->close();
		}
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
			$old = DATA_ROOT . DIRECTORY_SEPARATOR . $file->filename;
			$new = sprintf('%s/association.auto.%d.sqlite', DATA_ROOT, $i--);

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
	static public function remove(string $file): void
	{
		if (preg_match('!\.\.+!', $file)
			|| !preg_match('!^[\w\d._-]+\.sqlite$!i', $file)
			|| $file == basename(DB_FILE)) {
			throw new UserException('Nom de fichier non valide.');
		}

		Utils::safe_unlink(DATA_ROOT . '/' . $file);
	}

	/**
	 * Download a backup file in the browser. If $file is NULL, then the current database will be dumped.
	 */
	static public function dump(?string $file = null): void
	{
		$config = Config::getInstance();
		$tmp_file = null;

		if (null === $file) {
			$name = sprintf('%s - Sauvegarde données - %s.sqlite', $config->get('org_name'), date('Y-m-d'));

			$tmp_file = tempnam(sys_get_temp_dir(), 'gdin');
			self::make($tmp_file);

			$file = $tmp_file;
		}
		else {
			if (preg_match('!\.\.+!', $file) || !preg_match('!^[\w\d._ -]+\.sqlite$!iu', $file)) {
				throw new UserException('Nom de fichier non valide.');
			}

			$name = sprintf('%s - %s', $config->get('org_name'), str_replace('association.', '', $file));
			$file = DATA_ROOT . '/' . $file;

			if (!file_exists($file)) {
				throw new UserException('Le fichier fourni n\'existe pas.');
			}
		}

		$hash_length = strlen(sha1(''));

		header('Content-type: application/octet-stream');
		header(sprintf('Content-Disposition: attachment; filename="%s"', $name));
		header(sprintf('Content-Length: %d', filesize($file) + $hash_length));

		readfile($file);

		// Append integrity hash
		echo sha1_file($file);

		if (null !== $tmp_file) {
			@unlink($tmp_file);
		}
	}

	/**
	 * Restore from a local backup
	 */
	static public function restoreFromLocal(string $file, ?Session $session): int
	{
		if (preg_match('!\.\.+!', $file) || !preg_match('!^[\w\d._ -]+\.sqlite$!iu', $file)) {
			throw new UserException('Nom de fichier non valide.');
		}

		if (!file_exists(DATA_ROOT . '/' . $file)) {
			throw new UserException('Le fichier fourni n\'existe pas.');
		}

		return self::restoreDB(DATA_ROOT . '/' . $file, $session, false);
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

		$r = self::restoreDB($file['tmp_name'], $session, true);

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
	 * @param  string $file Absolute path
	 * @param  int $logged_user_id
	 * @param  bool $check_foreign_keys
	 * @return integer:
	 * - 1 if everything is OK
	 * - & self::NEED_UPGRADE if database version is older and requires an upgrade
	 * - & self::NOT_AN_ADMIN if in the restored database the logged user ID passed is not a config admin
	 * - & self::
	 */
	static protected function restoreDB(string $file, ?Session $session, bool $check_foreign_keys = false): int
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

		// Check for AppID
		$appid = $db->querySingle('PRAGMA application_id;', false);

		if ($appid !== DB::APPID) {
			throw new UserException('Ce fichier n\'est pas une sauvegarde Paheko (application_id ne correspond pas).', self::NO_APP_ID);
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

		$backup = str_replace('.sqlite', date('.Y-m-d-His') . '.avant_restauration.sqlite', DB_FILE);

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

		// Force user to be re-logged as the first admin
		if ($session && $session->isLogged(false)) {
			$return |= self::CHANGED_USER;
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

		$name = Utils::basename($file);
		$name = str_replace(['.sqlite', 'association.'], '', $name);
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

		foreach (glob(DATA_ROOT . '/*.sqlite') as $f) {
			if ($f === DB_FILE) {
				continue;
			}

			$size += filesize($f);
		}

		return $size;
	}
}