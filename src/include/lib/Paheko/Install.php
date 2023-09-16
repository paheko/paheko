<?php

namespace Paheko;

use Paheko\Accounting\Charts;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Year;
use Paheko\Entities\Users\Category;
use Paheko\Entities\Users\User;
use Paheko\Entities\Files\File;
use Paheko\Entities\Search;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;
use Paheko\Files\Files;
use Paheko\Files\Storage;
use Paheko\Plugins;
use Paheko\UserTemplate\Modules;

use KD2\HTTP;

/**
 * Pour procéder à l'installation de l'instance Paheko
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
	/**
	 * This sends the current installed version, as well as the PHP and SQLite versions
	 * for statistics purposes.
	 *
	 * You can disable this by setting DISABLE_INSTALL_PING to TRUE in CONFIG_FILE
	 */
	static public function ping(): void
	{
		if (DISABLE_INSTALL_PING) {
			return;
		}

		$options = '';
		$db = new \SQLite3(':memory:');
		$res = $db->query('PRAGMA compile_options;');

		while ($row = $res->fetchArray(\SQLITE3_NUM)) {
			if (0 !== strpos($row[0], 'ENABLE_')) {
				continue;
			}

			$options .= substr($row[0], strlen('ENABLE_')) . ',';
		}

		(new HTTP)->POST(PING_URL, [
			'id'      => sha1(WWW_URL . SECRET_KEY . ROOT),
			'version' => paheko_version(),
			'sqlite'  => \SQLite3::version()['versionString'],
			'php'     => PHP_VERSION,
			'sqlite_options' => trim($options, ', '),
		]);
	}

	/**
	 * Reset the database to empty and create a new user with the same password
	 */
	static public function reset(Session $session, string $password, array $options = [])
	{
		$config = (object) Config::getInstance()->asArray();
		$user = $session->getUser();

		if (!$session->checkPassword($password, $user->password)) {
			throw new UserException('Le mot de passe ne correspond pas.');
		}

		if (!trim($config->org_name)) {
			throw new UserException('Le nom de l\'association est vide, merci de le renseigner dans la configuration.');
		}

		if (!trim($user->name())) {
			throw new UserException('L\'utilisateur connecté ne dispose pas de nom, merci de le renseigner.');
		}

		if (!trim($user->email())) {
			throw new UserException('L\'utilisateur connecté ne dispose pas d\'adresse e-mail, merci de la renseigner.');
		}

		$name = date('Y-m-d-His-') . 'avant-remise-a-zero';

		Backup::create($name);

		// Keep a backup file of files
		if (FILE_STORAGE_BACKEND == 'FileSystem') {
			$name = 'documents_' . $name . '.zip';
			Files::zipAll(CACHE_ROOT . '/' . $name);
			Files::callStorage('truncate');
			@mkdir(FILE_STORAGE_CONFIG . '/documents');
			@rename(CACHE_ROOT . '/' . $name, FILE_STORAGE_CONFIG . '/documents/' . $name);
		}

		Config::deleteInstance();
		DB::getInstance()->close();
		DB::deleteInstance();

		file_put_contents(CACHE_ROOT . '/reset', json_encode([
			'password'     => $session::hashPassword($password),
			'name'         => $user->name(),
			'email'        => $user->email(),
			'organization' => $config->org_name,
			'country'      => $config->country,
		]));

		rename(DB_FILE, sprintf(DATA_ROOT . '/association.%s.sqlite', date('Y-m-d-His-') . 'avant-remise-a-zero'));

		self::showProgressSpinner('!install.php', 'Remise à zéro en cours…');
		exit;
	}

	/**
	 * Continues reset after page reload
	 */
	static public function checkReset()
	{
		if (!file_exists(CACHE_ROOT . '/reset')) {
			return;
		}

		$data = json_decode(file_get_contents(CACHE_ROOT . '/reset'));

		if (!$data) {
			throw new \LogicException('Invalid reset data');
		}

		try {
			// We can't use the real password, as it might not be valid (too short or compromised)
			$ok = self::install($data->country ?? 'FR', $data->organization ?? 'Association', $data->name, $data->email, md5($data->password));

			// Restore password
			DB::getInstance()->preparedQuery('UPDATE users SET password = ? WHERE id = 1;', $data->password);
			$session = Session::getInstance();
			$session->logout();
			$session->forceLogin(1);
		}
		catch (\Exception $e) {
			Config::deleteInstance();
			DB::getInstance()->close();
			DB::deleteInstance();
			Utils::safe_unlink(DB_FILE);
			throw $e;
		}

		@unlink(CACHE_ROOT . '/reset');

		Utils::redirect('!config/advanced/?msg=RESET');
	}

	static protected function assert(bool $assertion, string $message)
	{
		if (!$assertion) {
			throw new ValidationException($message);
		}
	}

	static public function installFromForm(array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		self::assert(isset($source['name']) && trim($source['name']) !== '', 'Le nom de l\'association n\'est pas renseigné');
		self::assert(isset($source['user_name']) && trim($source['user_name']) !== '', 'Le nom du membre n\'est pas renseigné');
		self::assert(isset($source['user_email']) && trim($source['user_email']) !== '', 'L\'adresse email du membre n\'est pas renseignée');
		self::assert(isset($source['password']) && isset($source['password_confirmed']) && trim($source['password']) !== '', 'Le mot de passe n\'est pas renseigné');

		self::assert((bool)filter_var($source['user_email'], FILTER_VALIDATE_EMAIL), 'Adresse email invalide');

		self::assert(strlen($source['password']) >= User::MINIMUM_PASSWORD_LENGTH, 'Le mot de passe est trop court');
		self::assert($source['password'] === $source['password_confirmed'], 'La vérification du mot de passe ne correspond pas');

		try {
			self::install($source['country'], $source['name'], $source['user_name'], $source['user_email'], $source['password']);
			self::ping();
		}
		catch (\Exception $e) {
			@unlink(DB_FILE);
			throw $e;
		}
	}

	static public function install(string $country_code, string $name, string $user_name, string $user_email, string $user_password): void
	{
		if (file_exists(DB_FILE)) {
			throw new UserException('La base de données existe déjà.');
		}

		self::checkAndCreateDirectories();
		Files::disableQuota();
		$db = DB::getInstance();

		$db->requireFeatures('cte', 'json_patch', 'fts4', 'date_functions_in_constraints', 'index_expressions', 'rename_column', 'upsert');

		// Création de la base de données
		$db->begin();
		$db->exec('PRAGMA application_id = ' . DB::APPID . ';');
		$db->setVersion(paheko_version());
		$db->exec(file_get_contents(DB_SCHEMA));
		$db->commit();

		file_put_contents(SHARED_CACHE_ROOT . '/version', paheko_version());

		$currency = $country_code == 'CH' ? 'CHF' : '€';

		// Configuration de base
		$config = Config::getInstance();
		$config->setCreateFlag();
		$config->import([
			'org_name'                 => $name,
			'org_email'                => $user_email,
			'currency'                 => $currency,
			'country'                  => $country_code,
			'site_disabled'            => true,
			'log_retention'            => 365,
			'auto_logout'              => 2*60,
			'analytical_set_all'       => true,
			'file_versioning_policy'   => 'min',
			'file_versioning_max_size' => 2,
		]);

		$fields = DynamicFields::getInstance();
		$fields->install();

		// Create default category for common users
		$cat = new Category;
		$cat->setAllPermissions(Session::ACCESS_NONE);
		$cat->importForm([
			'name' => 'Membres actifs',
			'perm_connect' => Session::ACCESS_READ,
		]);
		$cat->save();

		$config->set('default_category', $cat->id());

		// Create default category for ancient users
		$cat = new Category;
		$cat->importForm([
			'name' => 'Anciens membres',
			'hidden' => 1,
		]);
		$cat->setAllPermissions(Session::ACCESS_NONE);
		$cat->save();

		// Create default category for admins
		$cat = new Category;
		$cat->importForm([
			'name' => 'Administrateurs',
		]);
		$cat->setAllPermissions(Session::ACCESS_ADMIN);
		$cat->save();

		// Create first user
		$user = new User;
		$user->set('id_category', $cat->id());
		$user->importForm([
			'numero'      => 1,
			'nom'         => $user_name,
			'email'       => $user_email,
			'pays'        => 'FR',
		]);

		$user->importSecurityForm(false, [
			'password' => $user_password,
			'password_confirmed' => $user_password,
		]);

		$user->save();

		$config->set('files', array_map(fn () => null, $config::FILES));

		$welcome_text = sprintf("Bienvenue dans l'administration de %s !\n\nUtilisez le menu à gauche pour accéder aux différentes sections.\n\nSi vous êtes perdu, n'hésitez pas à consulter l'aide :-)", $name);

		$config->setFile('admin_homepage', $welcome_text);

        // Import accounting chart
        $chart = Charts::installCountryDefault($country_code);

		// Create an example saved search (users)
		$query = (object) [
			'groups' => [[
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'lettre_infos',
						'operator' => '= 1',
						'values'   => [],
					],
				],
			]],
			'order' => 'numero',
			'desc' => true,
			'limit' => '10000',
		];

		$search = new Search;
		$search->import([
			'label'   => 'Inscrits à la lettre d\'information',
			'target'  => $search::TARGET_USERS,
			'type'    => $search::TYPE_JSON,
			'content' => json_encode($query),
		]);
		$search->created = new \DateTime;
		$search->save();

		// Create an example saved search (accounting)
		$query = (object) [
			'groups' => [[
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'p.code',
						'operator' => 'IS NULL',
						'values'   => [],
					],
				],
			]],
			'order' => 't.id',
			'desc' => false,
			'limit' => '100',
		];


		$search = new Search;
		$search->import([
			'label'   => 'Écritures sans projet',
			'target'  => $search::TARGET_ACCOUNTING,
			'type'    => $search::TYPE_JSON,
			'content' => json_encode($query),
		]);
		$search->created = new \DateTime;
		$search->save();

		$config->save();

		Plugins::refresh();
		Modules::refresh();

		// Install welcome plugin if available
		$has_welcome_plugin = Plugins::exists('welcome');

		if ($has_welcome_plugin) {
			Plugins::install('welcome');
		}

		if (FILE_STORAGE_BACKEND != 'SQLite') {
			Storage::sync();
		}

		Files::enableQuota();
	}

	static public function checkAndCreateDirectories()
	{
		// Vérifier que les répertoires vides existent, sinon les créer
		$paths = [
			DATA_ROOT,
			CACHE_ROOT,
			SHARED_CACHE_ROOT,
			USER_TEMPLATES_CACHE_ROOT,
			STATIC_CACHE_ROOT,
			SMARTYER_CACHE_ROOT,
			SHARED_USER_TEMPLATES_CACHE_ROOT,
		];

		foreach ($paths as $path)
		{
			$index_file = $path . '/index.html';
			Utils::safe_mkdir($path, 0777, true);

			if (!is_dir($path))
			{
				throw new \RuntimeException('Le répertoire '.$path.' n\'existe pas ou n\'est pas un répertoire.');
			}

			// On en profite pour vérifier qu'on peut y lire et écrire
			if (!is_writable($path) || !is_readable($path))
			{
				throw new \RuntimeException('Le répertoire '.$path.' n\'est pas accessible en lecture/écriture.');
			}
			if (file_exists($index_file) AND (!is_writable($index_file) || !is_readable($index_file))) {
				throw new \RuntimeException('Le fichier ' . $index_file . ' n\'est pas accessible en lecture/écriture.');
			}

			// Some basic safety against misconfigured hosts
			file_put_contents($index_file, '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>');
		}

		return true;
	}

	static public function setLocalConfig(string $key, $value, bool $overwrite = true): void
	{
		$path = ROOT . DIRECTORY_SEPARATOR . CONFIG_FILE;

		if (!is_writable(ROOT)) {
			throw new \RuntimeException('Impossible de créer le fichier de configuration "'. CONFIG_FILE .'". Le répertoire "'. ROOT . '" n\'est pas accessible en écriture.');
		}

		$new_line = sprintf('const %s = %s;', $key, var_export($value, true));

		if (@filesize($path)) {
			$config = file_get_contents($path);

			$pattern = sprintf('/^.*(?:const\s+%s|define\s*\(.*%1$s).*$/m', $key);

			$config = preg_replace($pattern, $new_line, $config, -1, $count);

			if ($count && !$overwrite) {
				return;
			}

			if (!$count) {
				$config = preg_replace('/\?>.*/s', '', $config);
				$config .= PHP_EOL . $new_line . PHP_EOL;
			}
		}
		else {
			$config = '<?php' . PHP_EOL
				. 'namespace Paheko;' . PHP_EOL . PHP_EOL
				. $new_line . PHP_EOL;
		}

		file_put_contents($path, $config);
	}

	static public function showProgressSpinner(?string $next = null, string $message = '')
	{
		$next = $next ? sprintf('<meta http-equiv="refresh" content="0;url=%s" />', Utils::getLocalURL($next)) : '';

		printf('<!DOCTYPE html>
		<html>
		<head>
		<meta charset="utf-8" />
		<style type="text/css">
		* { padding: 0; margin: 0; }
		html {
			height: 100%%;
		}
		body {
			font-family: sans-serif;
			text-align: center;
			display: flex;
			align-items: center;
			justify-content: center;
			height: 100%%;
		}
		h2, p {
			margin-bottom: 1rem;
		}
		div {
			position: relative;
			max-width: 500px;
			padding: 1em;
			border-radius: .5em;
			background: #ccc;
		}
		.spinner h2::after {
			display: block;
			content: " ";
			margin: 1rem auto;
			width: 50px;
			height: 50px;
			border: 5px solid #999;
			border-radius: 50%%;
			border-top-color: #000;
			animation: spin 1s ease-in-out infinite;
		}

		@keyframes spin { to { transform: rotate(360deg); } }
		</style>
		%s
		</head>
		<body>
		<div class="spinner">
			<h2>%s</h2>
		</div>', $next, nl2br(htmlspecialchars($message)));

		flush();
	}
}
