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

        return true;
    }

    public function keepSessionAlive()
    {
        $this->_sessionStart(true);
    }

    public function login($id, $passe)
    {
        $db = DB::getInstance();
        $champ_id = Config::getInstance()->get('champ_identifiant');

        $r = $db->simpleQuerySingle('SELECT id, passe, id_categorie FROM membres WHERE '.$champ_id.' = ? LIMIT 1;', true, trim($id));

        if (empty($r))
            return false;

        if (!$this->_checkPassword(trim($passe), $r['passe']))
            return false;

        $droits = $this->getDroits($r['id_categorie']);

        if ($droits['connexion'] == self::DROIT_AUCUN)
            return false;

        $this->_sessionStart(true);
        $db->simpleExec('UPDATE membres SET date_connexion = datetime(\'now\') WHERE id = ?;', $r['id']);

        return $this->updateSessionData($r['id'], $droits);
    }

    public function recoverPasswordCheck($id)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        $champ_id = $config->get('champ_identifiant');

        $membre = $db->simpleQuerySingle('SELECT id, email FROM membres WHERE '.$champ_id.' = ? LIMIT 1;', true, trim($id));

        if (!$membre || trim($membre['email']) == '')
        {
            return false;
        }

        $this->_sessionStart(true);
        $hash = sha1($membre['email'] . $membre['id'] . 'recover' . ROOT . time());
        $_SESSION['recover_password'] = [
            'id' => (int) $membre['id'],
            'email' => $membre['email'],
            'hash' => $hash
        ];

        $message = "Bonjour,\n\nVous avez oublié votre mot de passe ? Pas de panique !\n\n";
        $message.= "Il vous suffit de cliquer sur le lien ci-dessous pour recevoir un nouveau mot de passe.\n\n";
        $message.= WWW_URL . 'admin/password.php?c=' . substr($hash, -10);
        $message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le, votre mot de passe restera inchangé.";

        return Utils::mail($membre['email'], '['.$config->get('nom_asso').'] Mot de passe perdu ?', $message);
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

        $password = Utils::suggestPassword();

        $dest = $_SESSION['recover_password']['email'];
        $id = (int)$_SESSION['recover_password']['id'];

        $message = "Bonjour,\n\nVous avez demandé un nouveau mot de passe pour votre compte.\n\n";
        $message.= "Votre adresse email : ".$dest."\n";
        $message.= "Votre nouveau mot de passe : ".$password."\n\n";
        $message.= "Si vous n'avez pas demandé à recevoir ce message, merci de nous le signaler.";

        $password = $this->_hashPassword($password);

        $db->simpleUpdate('membres', ['passe' => $password], 'id = '.(int)$id);

        return Utils::mail($dest, '['.$config->get('nom_asso').'] Nouveau mot de passe', $message);
    }

    public function updateSessionData($membre = null, $droits = null)
    {
        if (is_null($membre))
        {
            $membre = $this->get($_SESSION['logged_user']['id']);
        }
        else
        {
            $membre = $this->get($membre);
        }

        if (is_null($droits))
        {
            $droits = $this->getDroits($membre['id_categorie']);
        }

        $membre['droits'] = $droits;
        $_SESSION['logged_user'] = $membre;
        return true;
    }

    public function localLogin()
    {
        if (!defined('Garradin\LOCAL_LOGIN'))
            return false;

        if (trim(LOCAL_LOGIN) == '')
            return false;

        $db = DB::getInstance();
        $config = Config::getInstance();
        $champ_id = $config->get('champ_identifiant');
        
        if (is_int(LOCAL_LOGIN) && $db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ? LIMIT 1;', true, LOCAL_LOGIN))
        {
            $this->_sessionStart(true);
            return $this->updateSessionData(LOCAL_LOGIN);
        }
        elseif ($id = $db->simpleQuerySingle('SELECT id FROM membres WHERE '.$champ_id.' = ? LIMIT 1;', true, LOCAL_LOGIN))
        {
            $this->_sessionStart(true);
            return $this->updateSessionData($membre);
        }

        throw new UserException('Le membre ' . LOCAL_LOGIN . ' n\'existe pas, merci de modifier la directive Garradin\LOCAL_LOGIN.');
    }

    public function isLogged()
    {
        $this->_sessionStart();

        if (empty($_SESSION['logged_user']))
        {
            if (defined('Garradin\LOCAL_LOGIN'))
            {
                return $this->localLogin();
            }

            return false;
        }

        return true;
    }

    public function getLoggedUser()
    {
        if (!$this->isLogged())
            return false;

        return $_SESSION['logged_user'];
    }

    public function logout()
    {
        $_SESSION = [];
        setcookie(session_name(), '', 0, '/');
        return true;
    }

    public function sessionStore($key, $value)
    {
        if (!isset($_SESSION['storage']))
        {
            $_SESSION['storage'] = [];
        }

        if ($value === null)
        {
            unset($_SESSION['storage'][$key]);
        }
        else
        {
            $_SESSION['storage'][$key] = $value;
        }

        return true;
    }

    public function sessionGet($key)
    {
        if (!isset($_SESSION['storage'][$key]))
        {
            return null;
        }

        return $_SESSION['storage'][$key];
    }

    public function sendMessage($dest, $sujet, $message, $copie = false)
    {
        if (!$this->isLogged())
        {
            throw new \LogicException('Cette fonction ne peut être appelée que par un utilisateur connecté.');
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
            Utils::mail($from, $sujet, $message);
        }

        return Utils::mail($dest, $sujet, $message, ['From' => $from]);
    }

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields(&$data, $check_editable = true, $check_password = true)
    {
        $champs = Config::getInstance()->get('champs_membres');

        foreach ($champs->getAll() as $key=>$config)
        {
            if (!$check_editable && (!empty($config['private']) || empty($config['editable'])))
            {
                unset($data[$key]);
                continue;
            }

            if (!isset($data[$key]) || (!is_array($data[$key]) && trim($data[$key]) === '')
                || (is_array($data[$key]) && empty($data[$key])))
            {
                if (!empty($config['mandatory']) && ($check_password || $key != 'passe'))
                {
                    throw new UserException('Le champ "' . $config['title'] . '" doit obligatoirement être renseigné.');
                }
                elseif (!empty($config['mandatory']))
                {
                    continue;
                }
            }

            if (isset($data[$key]))
            {
                if ($config['type'] == 'email' && trim($data[$key]) !== '' && !filter_var($data[$key], FILTER_VALIDATE_EMAIL))
                {
                    throw new UserException('Adresse e-mail invalide dans le champ "' . $config['title'] . '".');
                }
                elseif ($config['type'] == 'url' && trim($data[$key]) !== '' && !filter_var($data[$key], FILTER_VALIDATE_URL))
                {
                    throw new UserException('Adresse URL invalide dans le champ "' . $config['title'] . '".');
                }
                elseif ($config['type'] == 'date' && trim($data[$key]) !== '' && !Utils::checkDate($data[$key]))
                {
                    throw new UserException('Date invalide "' . $config['title'] . '", format attendu : AAAA-MM-JJ.');
                }
                elseif ($config['type'] == 'datetime' && trim($data[$key]) !== '')
                {
                    if (!Utils::checkDateTime($data[$key]) || !($dt = new DateTime($data[$key])))
                    {
                        throw new UserException('Date invalide "' . $config['title'] . '", format attendu : AAAA-MM-JJ HH:mm.');
                    }

                    $data[$key] = $dt->format('Y-m-d H:i');
                }
                elseif ($config['type'] == 'tel')
                {
                    $data[$key] = Utils::normalizePhoneNumber($data[$key]);
                }
                elseif ($config['type'] == 'country')
                {
                    $data[$key] = strtoupper(substr($data[$key], 0, 2));
                }
                elseif ($config['type'] == 'checkbox')
                {
                    $data[$key] = empty($data[$key]) ? 0 : 1;
                }
                elseif ($config['type'] == 'number' && trim($data[$key]) !== '')
                {
                    if (empty($data[$key]))
                    {
                        $data[$key] = 0;
                    }

                    if (!is_numeric($data[$key]))
                        throw new UserException('Le champ "' . $config['title'] . '" doit contenir un chiffre.');
                }
                elseif ($config['type'] == 'select' && !in_array($data[$key], $config['options']))
                {
                    throw new UserException('Le champ "' . $config['title'] . '" ne correspond pas à un des choix proposés.');
                }
                elseif ($config['type'] == 'multiple')
                {
                    if (empty($data[$key]) || !is_array($data[$key]))
                    {
                        $data[$key] = 0;
                        continue;
                    }

                    $binary = 0;

                    foreach ($data[$key] as $k => $v)
                    {
                        if (array_key_exists($k, $config['options']) && !empty($v))
                        {
                            $binary |= 0x01 << $k;
                        }
                    }

                    $data[$key] = $binary;
                }

                // Un champ texte vide c'est un champ NULL
                if (is_string($data[$key]) && trim($data[$key]) === '')
                {
                    $data[$key] = null;
                }
            }
        }

        if (isset($data['code_postal']) && trim($data['code_postal']) != '')
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

        return true;
    }

    public function add($data = [])
    {
        $this->_checkFields($data);
        $db = DB::getInstance();
        $config = Config::getInstance();
        $id = $config->get('champ_identifiant');

        if (!empty($data[$id])
            && $db->simpleQuerySingle('SELECT 1 FROM membres WHERE '.$id.' = ? LIMIT 1;', false, $data[$id]))
        {
            throw new UserException('La valeur du champ '.$id.' est déjà utilisée par un autre membre, hors ce champ doit être unique à chaque membre.');
        }

        if (isset($data['passe']) && trim($data['passe']) != '')
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
        $id = $db->lastInsertRowId();

        Plugin::fireSignal('membre.nouveau', array_merge(['id' => $id], $data));

        return $id;
    }

    public function edit($id, $data = [], $check_editable = true)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        if (isset($data['id']) && ($data['id'] == $id || empty($data['id'])))
        {
            unset($data['id']);
        }

        $this->_checkFields($data, $check_editable, false);
        $champ_id = $config->get('champ_identifiant');

        if (!empty($data[$champ_id])
            && $db->simpleQuerySingle('SELECT 1 FROM membres WHERE '.$champ_id.' = ? AND id != ? LIMIT 1;', false, $data[$champ_id], (int)$id))
        {
            throw new UserException('La valeur du champ '.$champ_id.' est déjà utilisée par un autre membre, hors ce champ doit être unique à chaque membre.');
        }

        if (!empty($data['id']))
        {
            if (!preg_match('/^\d+$/', $data['id']))
            {
                throw new UserException('Le numéro de membre ne doit contenir que des chiffres.');
            }

            if ($db->simpleQuerySingle('SELECT 1 FROM membres WHERE id = ?;', false, (int)$data['id']))
            {
                throw new UserException('Ce numéro est déjà attribué à un autre membre.');
            }

            // Si on ne vérifie pas toutes les tables qui sont liées ici à un ID de membre
            // la requête de modification provoquera une erreur de contrainte de foreign key
            // ce qui est normal. Donc : il n'est pas possible de changer l'ID d'un membre qui
            // a participé au wiki, à la compta, etc.
            if ($db->simpleQuerySingle('SELECT 1 FROM wiki_revisions WHERE id_auteur = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_auteur = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM compta_rapprochement WHERE id_auteur = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM membres_operations WHERE id_membre = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM cotisations_membres WHERE id_membre = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM rappels_envoyes WHERE id_membre = ?;', false, (int)$id)
                || $db->simpleQuerySingle('SELECT 1 FROM fichiers_membres WHERE id = ?;', false, (int)$id))
            # FIXME || $db->simpleQuerySingle('SELECT 1 FROM wiki_suivi WHERE id_membre = ?;', false, (int)$id))
            {
                throw new UserException('Le numéro n\'est pas modifiable pour ce membre car des contenus sont liés à ce numéro de membre (wiki, compta, etc.).');
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

        if (empty($data))
        {
            return true;
        }

        return $db->simpleUpdate('membres', $data, 'id = '.(int)$id);
    }

    public function get($id)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        return $db->simpleQuerySingle('SELECT *,
            '.$config->get('champ_identite').' AS identite,
            strftime(\'%s\', date_inscription) AS date_inscription,
            strftime(\'%s\', date_connexion) AS date_connexion
            FROM membres WHERE id = ? LIMIT 1;', true, (int)$id);
    }

    public function delete($ids)
    {
        if (!is_array($ids))
        {
            $ids = [(int)$ids];
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
        $config = Config::getInstance();

        return $db->simpleQuerySingle('SELECT '.$config->get('champ_identite').' FROM membres WHERE id = ? LIMIT 1;', false, (int)$id);
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

    public function search($field, $query)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        $champs = $config->get('champs_membres');

        if ($field != 'id' && !$champs->get($field))
        {
            throw new \UnexpectedValueException($field . ' is not a valid field');
        }

        $champ = $champs->get($field);

        if ($champ['type'] == 'multiple')
        {
            $where = 'WHERE '.$field.' & (1 << '.(int)$query.')';
            $order = false;
        }
        elseif ($champ['type'] == 'tel')
        {
            $query = Utils::normalizePhoneNumber($query);
            $query = preg_replace('!^0+!', '', $query);

            if ($query == '')
            {
                return false;
            }

            $where = 'WHERE '.$field.' LIKE \'%'.$db->escapeString($query).'\'';
            $order = $field;
        }
        elseif (!$champs->isText($field))
        {
            $where = 'WHERE '.$field.' = \''.$db->escapeString($query).'\'';
            $order = $field;
        }
        else
        {
            $where = 'WHERE transliterate_to_ascii('.$field.') LIKE transliterate_to_ascii(\'%'.$db->escapeString($query).'%\')';
            $order = 'transliterate_to_ascii('.$field.') COLLATE NOCASE';
        }

        $fields = array_keys($champs->getListedFields());

        if (!in_array($field, $fields))
        {
            $fields[] = $field;
        }

        if (!in_array('email', $fields))
        {
            $fields[] = 'email';
        }

        return $db->simpleStatementFetch(
            'SELECT id, id_categorie, ' . implode(', ', $fields) . ',
                '.$config->get('champ_identite').' AS identite,
                strftime(\'%s\', date_inscription) AS date_inscription
                FROM membres ' . $where . ($order ? ' ORDER BY ' . $order : '') . '
                LIMIT 1000;',
            SQLITE3_ASSOC
        );
    }

    public function listByCategory($cat, $fields, $page = 1, $order = null, $desc = false)
    {
        $begin = ($page - 1) * self::ITEMS_PER_PAGE;

        $db = DB::getInstance();
        $config = Config::getInstance();

        $champs = $config->get('champs_membres');

        if (is_int($cat) && $cat)
            $where = 'WHERE id_categorie = '.(int)$cat;
        elseif (is_array($cat))
            $where = 'WHERE id_categorie IN ('.implode(',', $cat).')';
        else
            $where = '';

        if (is_null($order) || !$champs->get($order))
            $order = 'id';

        if (!empty($fields) && $order != 'id' && $champs->isText($order))
        {
            $order = 'transliterate_to_ascii('.$order.') COLLATE NOCASE';
        }

        if ($desc)
        {
            $order .= ' DESC';
        }

        if (!in_array('email', $fields))
        {
            $fields []= 'email';
        }

        $fields = implode(', ', $fields);

        $query = 'SELECT id, id_categorie, '.$fields.', '.$config->get('champ_identite').' AS identite,
            strftime(\'%s\', date_inscription) AS date_inscription
            FROM membres '.$where.'
            ORDER BY '.$order.' LIMIT ?, ?;';

        return $db->simpleStatementFetch($query, SQLITE3_ASSOC, $begin, self::ITEMS_PER_PAGE);
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

    static public function changeCategorie($id_cat, $membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        $db = DB::getInstance();
        return $db->simpleUpdate('membres',
            ['id_categorie' => (int)$id_cat],
            'id IN ('.implode(',', $membres).')'
        );
    }

    static protected function _deleteMembres($membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        Plugin::fireSignal('membre.suppression', $membres);

        $membres = implode(',', $membres);

        $db = DB::getInstance();
        
        // Mise à jour des références, membre qui n'existe plus
        $db->exec('UPDATE wiki_revisions SET id_auteur = NULL WHERE id_auteur IN ('.$membres.');');
        $db->exec('UPDATE compta_journal SET id_auteur = NULL WHERE id_auteur IN ('.$membres.');');
        $db->exec('UPDATE compta_rapprochement SET id_auteur = NULL WHERE id_auteur IN ('.$membres.');');

        // Suppression des données liées au membre
        $db->exec('DELETE FROM rappels_envoyes WHERE id_membre IN ('.$membres.');');
        $db->exec('DELETE FROM membres_operations WHERE id_membre IN ('.$membres.');');
        $db->exec('DELETE FROM cotisations_membres WHERE id_membre IN ('.$membres.');');

        //$db->exec('DELETE FROM wiki_suivi WHERE id_membre IN ('.$membres.');');
        
        // Suppression du membre
        return $db->exec('DELETE FROM membres WHERE id IN ('.$membres.');');
    }

    public function sendMessageToCategory($dest, $sujet, $message, $subscribed_only = false)
    {
        $config = Config::getInstance();

        $headers = [
            'From'  =>  '"'.$config->get('nom_asso').'" <'.$config->get('email_asso').'>',
        ];
        $message .= "\n\n--\n".$config->get('nom_asso')."\n".$config->get('site_asso');

        if ($dest == 0)
            $where = 'id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)';
        else
            $where = 'id_categorie = '.(int)$dest;

        // FIXME: filtrage plus intelligent, car le champ lettre_infos peut ne pas exister
        if ($subscribed_only)
        {
            $champs = Config::getInstance()->get('champs_membres');

            if ($champs->get('lettre_infos'))
            {
                $where .= ' AND lettre_infos = 1';
            }
        }

        $db = DB::getInstance();
        $res = $db->query('SELECT email FROM membres WHERE LENGTH(email) > 0 AND '.$where.' ORDER BY id;');

        $sujet = '['.$config->get('nom_asso').'] '.$sujet;

        while ($row = $res->fetchArray(SQLITE3_ASSOC))
        {
            Utils::mail($row['email'], $sujet, $message, $headers);
        }

        return true;
    }

    public function searchSQL($query)
    {
        $db = DB::getInstance();

        if (!preg_match('/LIMIT\s+/i', $query))
        {
            $query = preg_replace('/;?\s*$/', '', $query);
            $query .= ' LIMIT 100';
        }

        if (preg_match('/;\s*(.+?)$/', $query))
        {
            throw new UserException('Une seule requête peut être envoyée en même temps.');
        }

        $st = $db->prepare($query);

        if (!$st->readOnly())
        {
            throw new UserException('Seules les requêtes en lecture sont autorisées.');
        }

        $res = $st->execute();
        $out = [];

        while ($row = $res->fetchArray(SQLITE3_ASSOC))
        {
            if (array_key_exists('passe', $row))
            {
                unset($row['passe']);
            }
            
            $out[] = $row;
        }

        return $out;
    }

    public function schemaSQL()
    {
        $db = DB::getInstance();

        $tables = [
            'membres'   =>  $db->querySingle('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'membres\';'),
            'categories'=>  $db->querySingle('SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = \'membres_categories\';'),
        ];

        return $tables;
    }
}

?>