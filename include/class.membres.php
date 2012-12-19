<?php

namespace Garradin;

class Membres
{
    const DROIT_AUCUN = 0;
    const DROIT_ACCES = 1;
    const DROIT_ECRITURE = 2;
    const DROIT_ADMIN = 9;

    const ITEMS_PER_PAGE = 50;

    protected function _getSalt($length)
    {
        $str = str_split('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
        shuffle($str);

        return implode('',
            array_rand(
                $str,
                $length)
        );
    }

    protected function _hashPassword($password)
    {
        $salt = '$2a$08$' . $this->_getSalt(22);
        return crypt($password, $salt);
    }

    protected function _checkPassword($password, $stored_hash)
    {
        return crypt($password, $stored_hash) == $stored_hash;
    }

    protected function _sessionStart($force = false)
    {
        if (!isset($_SESSION) && ($force || isset($_COOKIE[session_name()])))
        {
            session_start();
        }

        // Fix bug with register_globals ($test is a reference to $_SESSION['test'])
        if (ini_get('register_globals') && isset($_SESSION))
        {
            foreach ($_SESSION as $key=>$value)
            {
                if (isset($GLOBALS[$key]))
                    unset($GLOBALS[$key]);
            }
        }

        return true;
    }

    public function keepSessionAlive()
    {
        $this->_sessionStart(true);
    }

    public function login($email, $passe)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;

        $db = DB::getInstance();
        $r = $db->simpleQuerySingle('SELECT *,
            strftime(\'%s\', date_connexion) AS date_connexion,
            strftime(\'%s\', date_inscription) AS date_inscription,
            strftime(\'%s\', date_cotisation) AS date_cotisation
            FROM membres WHERE email = ? LIMIT 1;', true, trim($email));

        if (empty($r))
            return false;

        if (!$this->_checkPassword(trim($passe), $r['passe']))
            return false;

        $droits = $this->getDroits($r['id_categorie']);

        if ($droits['connexion'] == self::DROIT_AUCUN)
            return false;

        $this->_sessionStart(true);
        $db->simpleExec('UPDATE membres SET date_connexion = datetime(\'now\') WHERE id = ?;', $r['id']);

        return $this->updateSessionData($r, $droits);
    }

    public function recoverPasswordCheck($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;

        $db = DB::getInstance();

        $id = $db->simpleQuerySingle('SELECT id FROM membres WHERE email = ? LIMIT 1;', false, trim($email));

        if (!$id)
        {
            return false;
        }

        $config = Config::getInstance();

        $dest = trim($email);

        $this->_sessionStart(true);
        $hash = sha1($dest . $id . 'recover' . GARRADIN_ROOT . time());
        $_SESSION['recover_password'] = array('id' => (int) $id, 'email' => $dest, 'hash' => $hash);

        $message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
        $message.= "Il vous suffit de cliquer sur le lien ci-dessous pour recevoir un nouveau mot de passe.\n\n";
        $message.= WWW_URL . 'admin/password.php?c=' . substr($hash, -10);
        $message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

        return utils::mail($dest, '['.$config->get('nom_asso').'] Mot de passe perdu ?', $message);
    }

    public function recoverPasswordConfirm($hash)
    {
        $this->_sessionStart();

        if (empty($_SESSION['recover_password']['hash']))
            return false;

        if (substr($_SESSION['recover_password']['hash'], -10) != $hash)
            return false;

        $config = Config::getInstance();
        $db = DB::getInstance();

        $password = utils::suggestPassword();

        $dest = $_SESSION['recover_password']['email'];
        $id = (int)$_SESSION['recover_password']['id'];

        $message = "Bonjour,\n\nVous avez demandé un nouveau mot de passe pour votre compte.\n\n";
        $message.= "Votre adresse email : ".$dest."\n";
        $message.= "Votre nouveau mot de passe : ".$password."\n\n";
        $message.= "Si vous n'avez pas demandé à recevoir ce message, merci de nous le signaler.";

        $password = $this->_hashPassword($password);

        $db->simpleUpdate('membres', array('passe' => $password), 'id = '.(int)$id);

        return utils::mail($dest, '['.$config->get('nom_asso').'] Nouveau mot de passe', $message);
    }

    public function updateSessionData($membre = null, $droits = null)
    {
        if (is_null($membre))
        {
            $membre = $this->get($_SESSION['logged_user']['id']);
        }

        if (is_null($droits))
        {
            $droits = $this->getDroits($membre['id_categorie']);
        }

        $membre['droits'] = $droits;
        $_SESSION['logged_user'] = $membre;
        return true;
    }

    public function isLogged()
    {
        $this->_sessionStart();

        return empty($_SESSION['logged_user']) ? false : true;
    }

    public function getLoggedUser()
    {
        if (!$this->isLogged())
            return false;

        return $_SESSION['logged_user'];
    }

    public function logout()
    {
        $_SESSION = array();
        setcookie(session_name(), '', 0, '/');
        return true;
    }

    public function sessionStore($key, $value)
    {
        if (!isset($_SESSION['storage']))
        {
            $_SESSION['storage'] = array();
        }

        $_SESSION['storage'][$key] = $value;
        return true;
    }

    public function sessionGet($key)
    {
        if (!isset($_SESSION['storage'][$key]))
            return null;

        return $_SESSION['storage'][$key];
    }

    public function sendMessage($dest, $sujet, $message, $copie = false)
    {
        if (!$this->isLogged())
        {
            throw new LogicException('Cette fonction ne peut être appelée que par un utilisateur connecté.');
        }

        $from = $this->getLoggedUser();
        $from = $from['email'];
        // Uniquement adresse email pour le moment car faudrait trouver comment
        // indiquer le nom mais qu'il soit correctement échappé FIXME

        $config = Config::getInstance();

        $message .= "\n\n--\nCe message a été envoyé par un membre de ".$config->get('nom_asso');
        $message .= ", merci de contacter ".$config->get('email_asso')." en cas d'abus.";

        if ($copie)
        {
            utils::mail($from, $sujet, $message);
        }

        return utils::mail($dest, $sujet, $message, array('From' => $from));
    }

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields(&$data, $check_mandatory = true)
    {
        if (isset($data['nom']) && !trim($data['nom']))
        {
            throw new UserException('Le champ prénom et nom ne peut rester vide.');
        }

        if ($check_mandatory)
        {
            $mandatory = Config::getInstance()->get('champs_obligatoires');

            foreach ($mandatory as $field)
            {
                if (array_key_exists($field, $data) && !trim($data[$field]))
                {
                    throw new UserException('Le champ \''.$field.'\' ne peut rester vide.');
                }
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new UserException('Adresse e-mail invalide.');
        }

        if (!empty($data['code_postal']))
        {
            if (!empty($data['pays']) && $data['pays'] == 'FR' && !preg_match('!^\d{5}$!', $data['code_postal']))
            {
                throw new UserException('Code postal invalide.');
            }
        }

        if (!empty($data['passe']) && strlen($data['passe']) < 5)
        {
            throw new UserException('Le mot de passe doit faire au moins 5 caractères.');
        }

        if (!empty($data['telephone']))
        {
            $data['telephone'] = preg_replace('![^\d\+]!', '', $data['telephone']);
        }

        if (isset($data['lettre_infos']))
        {
            $data['lettre_infos'] = (int) (bool) $data['lettre_infos'];
        }

        return true;
    }

    public function add($data = array())
    {
        $this->_checkFields($data);
        $db = DB::getInstance();

        if (!empty($data['email'])
            && $db->simpleQuerySingle('SELECT 1 FROM membres WHERE email = ? LIMIT 1;', false, $data['email']))
        {
            throw new UserException('Cette adresse e-mail est déjà utilisée par un autre membre, il faut en choisir une autre.');
        }

        if (!empty($data['passe']) && trim($data['passe']))
        {
            $data['passe'] = $this->_hashPassword($data['passe']);
        }
        else
        {
            unset($data['passe']);
        }

        if (empty($data['id_categorie']))
        {
            $data['id_categorie'] = Config::getInstance()->get('categorie_membres');
        }

        $db->simpleInsert('membres', $data);
        return $db->lastInsertRowId();
    }

    public function edit($id, $data = array(), $check_mandatory = true)
    {
        $db = DB::getInstance();

        if (isset($data['id']) && ($data['id'] == $id || empty($data['id'])))
        {
            unset($data['id']);
        }

        $this->_checkFields($data, $check_mandatory);

        if (!empty($data['email'])
            && $db->simpleQuerySingle('SELECT 1 FROM membres WHERE email = ? AND id != ? LIMIT 1;', false, $data['email'], (int)$id))
        {
            throw new UserException('Cette adresse e-mail est déjà utilisée par un autre membre, il faut en choisir une autre.');
        }

        if (!empty($data['id']))
        {
            if ($db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int)$data['id']))
            {
                throw new UserException('Ce numéro est déjà attribué à un autre membre.');
            }

            // Si on ne vérifie pas toutes les tables qui sont liées ici à un ID de membre
            // la requête de modification provoquera une erreur de contrainte de foreign key
            // ce qui est normal. Donc : il n'est pas possible de changer l'ID d'un membre qui
            // a participé au wiki, à la compta, etc.
            if ($db->simpleQuerySingle('SELECT 1 FROM wiki_revisions WHERE id_auteur = ?;', false, (int)$id))
            #|| $db->simpleQuerySingle('SELECT 1 FROM wiki_suivi WHERE id_membre = ?;', false, (int)$id))
            {
                throw new UserException('Le numéro n\'est pas modifiable pour ce membre, en effet des contenus sont liés à ce numéro de membre (wiki, compta, etc.).');
            }
        }

        if (!empty($data['passe']) && trim($data['passe']))
        {
            $data['passe'] = $this->_hashPassword($data['passe']);
        }
        else
        {
            unset($data['passe']);
        }

        if (isset($data['id_categorie']) && empty($data['id_categorie']))
        {
            $data['id_categorie'] = Config::getInstance()->get('categorie_membres');
        }

        $db->simpleUpdate('membres', $data, 'id = '.(int)$id);
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT *,
            strftime(\'%s\', date_connexion) AS date_connexion,
            strftime(\'%s\', date_inscription) AS date_inscription,
            strftime(\'%s\', date_cotisation) AS date_cotisation
            FROM membres WHERE id = ? LIMIT 1;', true, (int)$id);
    }

    public function delete($ids)
    {
        if (!is_array($ids))
        {
            $ids = array((int)$ids);
        }

        if ($this->isLogged())
        {
            $user = $this->getLoggedUser();

            foreach ($ids as $id)
            {
                if ($user['id'] == $id)
                {
                    throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
                }
            }
        }

        return self::_deleteMembres($ids);
    }

    public function getNom($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT nom FROM membres WHERE id = ? LIMIT 1;', false, (int)$id);
    }

    public function getDroits($id)
    {
        $db = DB::getInstance();
        $droits = $db->simpleQuerySingle('SELECT * FROM membres_categories WHERE id = ?;', true, (int)$id);

        foreach ($droits as $key=>$value)
        {
            unset($droits[$key]);
            $key = str_replace('droit_', '', $key, $found);

            if ($found)
            {
                $droits[$key] = (int) $value;
            }
        }

        return $droits;
    }

    public function search($field = null, $query)
    {
        $db = DB::getInstance();

        if (is_null($field) || !in_array($field, array('id', 'nom', 'email', 'code_postal', 'ville', 'adresse', 'telephone')))
        {
            $field = 'nom';
        }

        if ($field == 'id' || $field == 'code_postal')
        {
            $where = 'WHERE '.$field.' = \''.$db->escapeString($query).'\'';
            $order = $field;
        }
        else
        {
            $where = 'WHERE transliterate_to_ascii('.$field.') LIKE transliterate_to_ascii(\'%'.$db->escapeString($query).'%\')';
            $order = 'transliterate_to_ascii('.$field.') COLLATE NOCASE';
        }

        return $db->simpleStatementFetch(
            'SELECT id, id_categorie, nom, email, code_postal, ville,
                strftime(\'%s\', date_cotisation) AS date_cotisation,
                strftime(\'%s\', date_inscription) AS date_inscription
                FROM membres '.$where.'
                ORDER BY '.$order.' LIMIT 100;',
            SQLITE3_ASSOC
        );
    }

    public function listByCategory($cat = 0, $page = 1, $order = 'nom', $desc = false)
    {
        $begin = ($page - 1) * self::ITEMS_PER_PAGE;

        $db = DB::getInstance();

        if (is_int($cat) && $cat)
            $where = 'WHERE id_categorie = '.(int)$cat;
        elseif (is_array($cat))
            $where = 'WHERE id_categorie IN ('.implode(',', $cat).')';
        else
            $where = '';

        if (!in_array($order, array('nom', 'id', 'ville', 'date_cotisation', 'date_inscription')))
            $order = 'nom';

        if ($order == 'nom' || $order == 'ville')
        {
            $order = 'transliterate_to_ascii('.$order.') COLLATE NOCASE';
        }

        if ($desc)
        {
            $order .= ' DESC';
        }

        return $db->simpleStatementFetch(
            'SELECT id, id_categorie, nom, email, code_postal, ville,
                strftime(\'%s\', date_cotisation) AS date_cotisation,
                strftime(\'%s\', date_inscription) AS date_inscription
                FROM membres '.$where.'
                ORDER BY '.$order.' LIMIT ?, ?;',
            SQLITE3_ASSOC,
            $begin,
            self::ITEMS_PER_PAGE
        );
    }

    public function countByCategory($cat = 0)
    {
        $db = DB::getInstance();

        if (is_int($cat) && $cat)
            $where = 'WHERE id_categorie = '.(int)$cat;
        elseif (is_array($cat))
            $where = 'WHERE id_categorie IN ('.implode(',', $cat).')';
        else
            $where = '';

        return $db->simpleQuerySingle('SELECT COUNT(*) FROM membres '.$where.';');
    }

    public function countAllButHidden()
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT COUNT(*) FROM membres WHERE id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1);');
    }

    static public function checkCotisation($date_membre, $duree_cotisation, $date_verif = null)
    {
        if (is_null($date_verif))
            $date_verif = time();

        if (!$date_membre)
            return false;

        $echeance = new \DateTime('@'.$date_membre);
        $echeance->setTime(0, 0);
        $echeance->modify('+'.$duree_cotisation.' months');

        if ($echeance->getTimestamp() < $date_verif)
            return round(($date_verif - $echeance->getTimestamp()) / 3600 / 24);

        return true;
    }

    static public function updateCotisation($id, $date)
    {
        if (preg_match('!^\d{2}/\d{2}/\d{4}$!', $date))
            $date = \DateTime::createFromFormat('d/m/Y', $date, new DateTimeZone('UTC'));
        elseif (preg_match('!^\d{4}-\d{2}-\d{2}$!', $date))
            $date = \DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('UTC'));
        else
            throw new UserException('Format de date invalide : '.$date);

        $db = DB::getInstance();
        return $db->simpleUpdate('membres',
            array('date_cotisation' => $date->format('Y-m-d H:i:s')),
            'id = '.(int)$id
        );
    }

    static public function changeCategorie($id_cat, $membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        $db = DB::getInstance();
        return $db->simpleUpdate('membres',
            array('id_categorie' => (int)$id_cat),
            'id IN ('.implode(',', $membres).')'
        );
    }

    static protected function _deleteMembres($membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        $membres = implode(',', $membres);

        $db = DB::getInstance();
        $db->exec('UPDATE wiki_revisions SET id_auteur = NULL WHERE id_auteur IN ('.$membres.');');
        $db->exec('UPDATE compta_journal SET id_auteur = NULL WHERE id_auteur IN ('.$membres.');');
        //$db->exec('DELETE FROM wiki_suivi WHERE id_membre IN ('.$membres.');');
        return $db->exec('DELETE FROM membres WHERE id IN ('.$membres.');');
    }

    public function sendMessageToCategory($dest, $sujet, $message, $subscribed_only = false)
    {
        $config = Config::getInstance();

        $headers = array(
            'From'  =>  '"'.$config->get('nom_asso').'" <'.$config->get('email_asso').'>',
        );
        $message .= "\n\n--\n".$config->get('nom_asso')."\n".$config->get('site_asso');

        if ($dest == 0)
            $where = 'id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)';
        else
            $where = 'id_categorie = '.(int)$dest;

        if ($subscribed_only)
        {
            $where .= ' AND lettre_infos = 1';
        }

        $db = DB::getInstance();
        $res = $db->query('SELECT email FROM membres WHERE LENGTH(email) > 0 AND '.$where.' ORDER BY id;');

        $sujet = '['.$config->get('nom_asso').'] '.$sujet;

        while ($row = $res->fetchArray(SQLITE3_ASSOC))
        {
            utils::mail($row['email'], $sujet, $message, $headers);
        }

        return true;
    }
}

?>