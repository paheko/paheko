<?php

namespace Garradin;

use Garradin\Accounting\Charts;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Year;
use Garradin\Entities\Users\Category;
use Garradin\Entities\Users\User;
use Garradin\Entities\Files\File;
use Garradin\Entities\Search;
use Garradin\Users\DynamicFields;
use Garradin\Users\Session;

use KD2\HTTP;

/**
 * Pour procéder à l'installation de l'instance Garradin
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
	/**
	 * This sends the current installed version, as well as the PHP and SQLite versions
	 * for statistics purposes.
	 *
	 * You can disable this by setting DISABLE_INSTALL_PING to TRUE in config.local.php
	 */
	static public function ping(): void
	{
		if (DISABLE_INSTALL_PING) {
			return;
		}

		(new HTTP)->POST(PING_URL, [
			'id'      => sha1(WWW_URL . SECRET_KEY . ROOT),
			'version' => garradin_version(),
			'sqlite'  => \SQLite3::version()['versionString'],
			'php'     => PHP_VERSION,
		]);
	}

	/**
	 * Reset the database to empty and create a new user with the same password
	 */
	static public function reset(Users\Session $session, string $password, array $options = [])
	{
		$config = (object) Config::getInstance()->asArray();
		$user = $session->getUser();

		if (!$session->checkPassword($password, $user->passe))
		{
			throw new UserException('Le mot de passe ne correspond pas.');
		}

		if (!trim($config->org_name)) {
			throw new UserException('Le nom de l\'association est vide, merci de le renseigner dans la configuration.');
		}

		if (!trim($user->identite)) {
			throw new UserException('L\'utilisateur connecté ne dispose pas de nom, merci de le renseigner.');
		}

		if (!trim($user->email)) {
			throw new UserException('L\'utilisateur connecté ne dispose pas d\'adresse e-mail, merci de la renseigner.');
		}

		(new Sauvegarde)->create(date('Y-m-d-His-') . 'avant-remise-a-zero');

		Config::deleteInstance();
		DB::getInstance()->close();
		DB::deleteInstance();

		file_put_contents(CACHE_ROOT . '/reset', json_encode([
			'password'     => $session::hashPassword($password),
			'name'         => $user->identite,
			'email'        => $user->email,
			'organization' => $config->org_name,
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

		// We can't use the real password, as it might not be valid (too short or compromised)
		$ok = self::install($data->organization ?? 'Association', $data->name, $data->email, md5($data->password));

		// Restore password
		DB::getInstance()->preparedQuery('UPDATE users SET password = ? WHERE id = 1;', [$data->password]);

		if (defined('\Garradin\LOCAL_LOGIN') && \Garradin\LOCAL_LOGIN) {
			Session::getInstance()->refresh();
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

	static public function installFromForm(array $source = null)
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
			$ok = self::install($source['name'], $source['user_name'], $source['user_email'], $source['password']);
			self::ping();
			return $ok;
		}
		catch (\Exception $e) {
			@unlink(DB_FILE);
			throw $e;
		}
	}

	static public function install(string $name, string $user_name, string $user_email, string $user_password, ?string $welcome_text = null): void
	{
		if (file_exists(DB_FILE)) {
			throw new UserException('La base de données existe déjà.');
		}

		self::checkAndCreateDirectories();
		$db = DB::getInstance();

		// Création de la base de données
		$db->begin();
		$db->exec('PRAGMA application_id = ' . DB::APPID . ';');
		$db->setVersion(garradin_version());
		$db->exec(file_get_contents(DB_SCHEMA));
		$db->commit();

		file_put_contents(SHARED_CACHE_ROOT . '/version', garradin_version());

		// Configuration de base
		$config = Config::getInstance();
		$config->setCreateFlag();
		$config->import([
			'org_name'      => $name,
			'org_email'     => $user_email,
			'currency'      => '€',
			'country'       => 'FR',
			'site_disabled' => true,
			'log_retention' => 365,
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
		$user->id_category = $cat->id();
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

		$welcome_text = $welcome_text ?? sprintf("Bienvenue dans l'administration de %s !\n\nUtilisez le menu à gauche pour accéder aux différentes sections.\n\nCe message peut être modifié dans la 'Configuration'.", $name);

		$config->setFile('admin_homepage', $welcome_text);

        // Import accounting chart
        $chart = Charts::install('fr_pca_2018');

        // Create first accounting year
        $year = new Year;
        $year->label = sprintf('Exercice %d', date('Y'));
        $year->start_date = new \DateTime('January 1st');
        $year->end_date = new \DateTime('December 31');
        $year->id_chart = $chart->id();
        $year->save();

        // Create a first bank account
        $account = new Account;
        $account->import([
        	'label' => 'Compte courant',
        	'code' => '512A',
        	'type' => Account::TYPE_BANK,
        	'position' => Account::ASSET_OR_LIABILITY,
        	'id_chart' => $chart->id(),
        	'user' => 1,
        ]);
        $account->save();

		// Create an example saved search (users)
		$query = (object) [
			'query' => [[
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
			'query' => [[
				'operator' => 'AND',
				'conditions' => [
					[
						'column'   => 'a2.code',
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

		// Install welcome plugin if available
		$has_welcome_plugin = Plugin::getPath('welcome', false);

		if ($has_welcome_plugin) {
			Plugin::install('welcome', true);
		}

		$config->save();
	}

	static public function checkAndCreateDirectories()
	{
		// Vérifier que les répertoires vides existent, sinon les créer
		$paths = [
			DATA_ROOT,
			PLUGINS_ROOT,
			CACHE_ROOT,
			SHARED_CACHE_ROOT,
			USER_TEMPLATES_CACHE_ROOT,
			STATIC_CACHE_ROOT,
			SMARTYER_CACHE_ROOT,
			SHARED_USER_TEMPLATES_CACHE_ROOT,
		];

		foreach ($paths as $path)
		{
			Utils::safe_mkdir($path, 0777, true);

			if (!is_dir($path))
			{
				throw new UserException('Le répertoire '.$path.' n\'existe pas ou n\'est pas un répertoire.');
			}

			// On en profite pour vérifier qu'on peut y lire et écrire
			if (!is_writable($path) || !is_readable($path))
			{
				throw new UserException('Le répertoire '.$path.' n\'est pas accessible en lecture/écriture.');
			}

			// Some basic safety against misconfigured hosts
			file_put_contents($path . '/index.html', '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>');
		}

		return true;
	}

	static public function setLocalConfig($key, $value)
	{
		$path = ROOT . DIRECTORY_SEPARATOR . 'config.local.php';
		$new_line = sprintf('const %s = %s;', $key, var_export($value, true));

		if (file_exists($path))
		{
			$config = file_get_contents($path);

			$pattern = sprintf('/^.*(?:const\s+%s|define\s*\(.*%1$s).*$/m', $key);

			$config = preg_replace($pattern, $new_line, $config, -1, $count);

			if (!$count)
			{
				$config = preg_replace('/\?>.*/s', '', $config);
				$config .= PHP_EOL . $new_line . PHP_EOL;
			}
		}
		else
		{
			$config = '<?php' . PHP_EOL
				. 'namespace Garradin;' . PHP_EOL . PHP_EOL
				. $new_line . PHP_EOL;
		}

		return file_put_contents($path, $config);
	}

	static public function showProgressSpinner(?string $next = null, string $message = '')
	{
		$next = $next ? sprintf('<meta http-equiv="refresh" content="0;url=%s" />', Utils::getLocalURL($next)) : '';

		printf('<!DOCTYPE html>
		<html>
		<head>
		<meta charset="utf-8" />
		<style type="text/css">
		body {
			font-family: sans-serif;
		}
		h2, p {
			margin: 0;
			margin-bottom: 1rem;
		}
		div {
			position: relative;
			border: 1px solid #999;
			max-width: 500px;
			padding: 1em;
			border-radius: .5em;
		}
		.spinner h2::after {
			display: block;
			content: " ";
			margin: 1rem auto;
			width: 50px;
			height: 50px;
			border: 5px solid #ccc;
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
		</div>', $next, htmlspecialchars($message));

		flush();
	}
}
