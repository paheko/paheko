<?php

namespace Garradin;

class Rappels_Envoyes
{
	/**
	 * Types de médias
	 */
	const MEDIA_EMAIL = 1;
	const MEDIA_COURRIER = 2;
	const MEDIA_TELEPHONE = 3;
	const MEDIA_AUTRE = 4;

	/**
	 * Nombre d'items par page dans les listes
	 */
	const ITEMS_PER_PAGE = 50;

	/**
	 * Vérification des champs fournis pour la modification de donnée
	 * @param  array $data Tableau contenant les champs à ajouter/modifier
	 * @return void
	 */
	protected function _checkFields(&$data)
	{
		$db = DB::getInstance();

		if (!array_key_exists('id_rappel', $data) 
			|| (!is_null($data['id_rappel']) && (empty($data['id_rappel']) || !$db->simpleQuerySingle('SELECT 1 FROM rappels WHERE id = ?;', false, (int) $data['id_rappel']))))
		{
			throw new \LogicException('ID rappel non fourni ou inexistant dans la table rappels');
		}

        if (isset($data['id_cotisation']))
        {
        	if (!$db->simpleQuerySingle('SELECT 1 FROM cotisations WHERE id = ?;', false, (int) $data['id_cotisation']))
	        {
	            throw new UserException('Cotisation inconnue.');
	        }

	        $data['id_cotisation'] = (int) $data['id_cotisation'];
	    }

        if (empty($data['id_membre'])
        	|| !$db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int) $data['id_membre']))
        {
            throw new UserException('Membre inconnu.');
        }

		$data['id_membre'] = (int) $data['id_membre'];

		if (empty($data['media']) || !is_numeric($data['media']) 
			|| !in_array((int)$data['media'], [self::MEDIA_EMAIL, self::MEDIA_COURRIER, self::MEDIA_TELEPHONE, self::MEDIA_AUTRE]))
		{
			throw new UserException('Média invalide.');
		}

		$data['media'] = (int) $data['media'];

		if (empty($data['date']) || !Utils::checkDate($data['date']))
		{
			throw new UserException('La date indiquée n\'est pas valide.');
		}
	}

	/**
	 * Enregistrer un rappel
	 * @param array $data Données du rappel
	 * @return integer Numéro ID du rappel créé
	 */
	public function add($data)
	{
		$db = DB::getInstance();

		$this->_checkFields($data);

		$db->simpleInsert('rappels_envoyes', $data);

		return $db->lastInsertRowId();
	}

	/**
	 * Supprimer un rappel enregistré
	 * @param  integer $id Numéro du rappel
	 * @return boolean     TRUE en cas de succès
	 */
	public function delete($id)
	{
		$db = DB::getInstance();
		$db->simpleExec('DELETE FROM rappels_envoyes WHERE id = ?;', (int) $id);
		return true;
	}

	/**
	 * Renvoie les données sur un rappel
	 * @param  integer $id Numéro du rappel
	 * @return array     Données du rappel
	 */
	public function get($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT * FROM rappels_envoyes WHERE id = ?;', true, (int)$id);
	}

	/**
	 * Remplacer les tags dans le contenu/sujet du mail
	 * @param  string $content Chaîne à traiter
	 * @param  array  $data    Données supplémentaires à utiliser comme tags (tableau associatif)
	 * @return string          $content dont les tags ont été remplacés par le contenu correct
	 */
	public function replaceTagsInContent($content, $data = null)
	{
		$config = Config::getInstance();
		$tags = [
			'#NOM_ASSO'		=>	$config->get('nom_asso'),
			'#ADRESSE_ASSO'	=>	$config->get('adresse_asso'),
			'#EMAIL_ASSO'	=>	$config->get('email_asso'),
			'#SITE_ASSO'	=>	$config->get('site_asso'),
			'#URL_RACINE'	=>	WWW_URL,
			'#URL_SITE'		=>	WWW_URL,
			'#URL_ADMIN'	=>	WWW_URL . 'admin/',
		];

		if (!empty($data) && is_array($data))
		{
			foreach ($data as $key=>$value)
			{
				$key = '#' . strtoupper($key);
				$tags[$key] = $value;
			}
		}

		return strtr($content, $tags);
	}

	/**
	 * Envoi de mail pour rappel automatisé
	 * @param  array $data Données du rappel automatisé
	 * @return boolean     TRUE
	 */
	public function sendAuto($data)
	{
		$replace = $data;
		$replace['date_rappel'] = Utils::sqliteDateToFrench($replace['date_rappel']);
		$replace['date_expiration'] = Utils::sqliteDateToFrench($replace['expiration']);
		$replace['nb_jours'] = abs($replace['nb_jours']);
		$replace['delai'] = abs($replace['delai']);

		$subject = $this->replaceTagsInContent($data['sujet'], $replace);
		$text = $this->replaceTagsInContent($data['texte'], $replace);

		// Envoi du mail
		Utils::mail($data['email'], $subject, $text);

		// Enregistrement en DB
		$this->add([
			'id_cotisation'	=>	$data['id_cotisation'],
			'id_membre'		=>	$data['id'],
			'id_rappel'		=>	$data['id_rappel'],
			'media'			=>	Rappels_Envoyes::MEDIA_EMAIL,
			// On enregistre la date de mise en œuvre du rappel
			// et non pas la date d'envoi effective du rappel
			// car l'envoi du rappel peut ne pas être effectué
			// le jour où il aurait dû être envoyé (la magie des cron)
			'date'			=>	$data['date_rappel'],
		]);

		return true;
	}

	/**
	 * Liste des rappels envoyés à un membre
	 * @param integer $id Numéro du membre
	 * @return array Liste des rappels
	 */
	public function listForMember($id)
	{
		return DB::getInstance()->simpleStatementFetch('SELECT
			re.*, c.intitule, c.montant
			FROM rappels_envoyes AS re 
				INNER JOIN cotisations AS c ON c.id = re.id_cotisation 
			WHERE re.id_membre = ?
			ORDER BY re.date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Liste des rappels pour une cotisation donnée
	 * @param  integer $id Numéro de la cotisation
	 * @param  integer $page Numéro de page de liste
	 * @return array     Liste des rappels
	 */
	public function listForCotisation($id, $page = 1)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		return DB::getInstance()->simpleStatementFetch('SELECT * FROM rappels_envoyes
			WHERE id_rappel IN (SELECT id FROM rappels WHERE id_cotisation = ?)
			ORDER BY date DESC;', \SQLITE3_ASSOC, (int)$id);
	}

	/**
	 * Nombre de rappels pour une cotisation donnée
	 * @param  integer $id Numéro de la cotisation
	 * @return integer Nombre de rappels envoyés
	 */
	public function countForCotisation($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT COUNT(*) FROM rappels_envoyes
			WHERE id_rappel IN (SELECT id FROM rappels WHERE id_cotisation = ?);',
			false, (int)$id);
	}

	/**
	 * Liste des rappels envoyés pour un rappel automatique
	 * @param  integer $id Numéro du rappel
	 * @param  integer $page Numéro de page de liste
	 * @return array Liste des rappels envoyés
	 */
	public function listForRappel($id, $page = 1)
	{
		$begin = ($page - 1) * self::ITEMS_PER_PAGE;

		return DB::getInstance()->simpleStatementFetch('SELECT * FROM rappels_envoyes 
			WHERE id_rappel = ? ORDER BY date DESC LIMIT ?,?;',
			\SQLITE3_ASSOC, (int)$id, (int)$begin, self::ITEMS_PER_PAGE);
	}

	/**
	 * Nombre de rappels envoyés pour un rappel automatique
	 * @param  integer $id Numéro du rappel
	 * @return integer Nombre de rappels envoyés pour ce rappel
	 */
	public function countForRappel($id)
	{
		return DB::getInstance()->simpleQuerySingle('SELECT COUNT(*) FROM rappels_envoyes 
			WHERE id_rappel = ?;', false, (int)$id);
	}
}