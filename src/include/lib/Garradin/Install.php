<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Chart;
use Garradin\Entities\Accounting\Year;
use Garradin\Entities\Users\Category;
use Garradin\Entities\Files\File;
use Garradin\Membres\Session;

/**
 * Pour procéder à l'installation de l'instance Garradin
 * Utile pour automatiser l'installation sans passer par la page d'installation
 */
class Install
{
	static public function reset(Membres\Session $session, $password, array $options = [])
	{
		$config = (object) Config::getInstance()->asArray();
		$user = $session->getUser();

		if (!$session->checkPassword($password, $user->passe))
		{
			throw new UserException('Le mot de passe ne correspond pas.');
		}

		(new Sauvegarde)->create(date('Y-m-d-His-') . 'avant-remise-a-zero');

		DB::getInstance()->close();
		Config::deleteInstance();

		unlink(DB_FILE);

		// We can't use the real password, as it might not be valid (too short or compromised)
		$ok = self::install($config->nom_asso, $user->identite, $user->email, md5($password . SECRET_KEY));

		// Restore password
		DB::getInstance()->preparedQuery('UPDATE membres SET passe = ? WHERE id = 1;', [$session::hashPassword($password)]);

		if ($ok)
		{
			// Force l'installation de plugin système
			Plugin::checkAndInstallSystemPlugins();
		}

		return $ok;
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

	static public function install(string $name, string $user_name, string $user_email, string $user_password, ?string $welcome_text = null)
	{
		self::checkAndCreateDirectories();
		$db = DB::getInstance(true);

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
		$config->set('nom_asso', $name);
		$config->set('email_asso', $user_email);
		$config->set('monnaie', '€');
		$config->set('pays', 'FR');
		$config->set('site_disabled', true);

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

		$config->set('categorie_membres', $cat->id());

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

		$welcome_text = $welcome_text ?? sprintf("Bienvenue dans l'administration de %s !\n\nUtilisez le menu à gauche pour accéder aux différentes sections.\n\nCe message peut être modifié dans la 'Configuration'.", $name);

		$path = Config::DEFAULT_FILES['admin_homepage'];

		$file = File::createAndStore(Utils::dirname($path), Utils::basename($path), null, $welcome_text);
		$config->set('admin_homepage', $file->path);

        // Import accounting chart
        $chart = new Chart;
        $chart->label = 'Plan comptable associatif 2020 (Règlement ANC n°2018-06)';
        $chart->country = 'FR';
        $chart->code = 'PCA2018';
        $chart->save();
        $chart->accounts()->importCSV(ROOT . '/include/data/charts/fr_2018.csv');

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

		return $config->save();
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
}
