<?php

namespace Garradin;

use Garradin\Accounting\Charts;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Year;
use Garradin\Entities\Users\Category;
use Garradin\Entities\Files\File;
use Garradin\Users\Session;

/**
 * Pour procéder à l'installation de l'instance Garradin
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
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
		DB::getInstance()->preparedQuery('UPDATE membres SET passe = ? WHERE id = 1;', [$data->password]);

		// Force l'installation de plugin système
		Plugin::checkAndInstallSystemPlugins();

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
		self::assert(isset($source['user_password']) && isset($source['user_password_confirm']) && trim($source['user_password']) !== '', 'Le mot de passe n\'est pas renseigné');

		self::assert((bool)filter_var($source['user_email'], FILTER_VALIDATE_EMAIL), 'Adresse email invalide');

		self::assert(strlen($source['user_password']) >= Session::MINIMUM_PASSWORD_LENGTH, 'Le mot de passe est trop court');
		self::assert($source['user_password'] === $source['user_password_confirm'], 'La vérification du mot de passe ne correspond pas');

		try {
			return self::install($source['name'], $source['user_name'], $source['user_email'], $source['user_password']);
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
		// c'est dans Config::set que sont vérifiées les données utilisateur (renvoie UserException)
		$config = Config::getInstance();
		$config->set('org_name', $name);
		$config->set('org_email', $user_email);
		$config->set('currency', '€');
		$config->set('country', 'FR');
		$config->set('site_disabled', true);
		$config->set('log_retention', 720);
		$config->set('log_anonymize', 365);

		$champs = Membres\Champs::importInstall();
		$champs->create(); // Pas de copie car pas de table membres existante
		$config->set('champs_membres', $champs);

		$config->set('champ_identifiant', 'email');
		$config->set('champ_identite', 'nom');

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
		$membres = new Membres;
		$id_membre = $membres->add([
			'id_category' => $cat->id(),
			'nom'         => $user_name,
			'email'       => $user_email,
			'passe'       => $user_password,
			'pays'        => 'FR',
		]);

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

		$recherche = new Recherche;
		$recherche->add('Inscrits à la lettre d\'information', null, $recherche::TYPE_JSON, 'membres', $query);

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

		$recherche = new Recherche;
		$recherche->add('Écritures sans projet', null, $recherche::TYPE_JSON, 'compta', $query);

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
			border: 5px solid #000;
			border-radius: 50%%;
			border-top-color: #999;
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
