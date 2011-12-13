<?php

class Garradin_Membres
{
    const DROIT_AUCUN = 0;
    const DROIT_ACCES = 1;
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
            @session_start();

        return true;
    }

    protected function _login($user)
    {
        $this->_sessionStart(true);

        $_SESSION['logged_user'] = $user;

        return true;
    }

    public function login($email, $passe)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;

        $db = Garradin_DB::getInstance();
        $r = $db->querySingle('SELECT * FROM membres WHERE email=\''.$db->escapeString($email).'\' LIMIT 1;', true);

        if (empty($r))
            return false;

        if (!$this->_checkPassword($passe, $r['passe']))
            return false;

        $droits = $db->simpleQuerySingle('SELECT * FROM membres_categories WHERE id = ?;', true, (int)$r['id_categorie']);

        foreach ($droits as $key=>$value)
        {
            unset($droits[$key]);
            $key = str_replace('droit_', '', $key, $found);

            if ($found)
            {
                $droits[$key] = (int) $value;
            }
        }

        if ($droits['connexion'] == self::DROIT_AUCUN)
            return false;

        $r['droits'] = $droits;

        $db->simpleExec('UPDATE membres SET date_connexion = datetime(\'now\') WHERE id = ?;', $r['id']);

        return $this->_login($r);
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

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields($data)
    {
        $mandatory = Garradin_Config::getInstance()->get('champs_obligatoires');

        foreach ($mandatory as $field)
        {
            if (!array_key_exists($field, $data) || !trim($data[$field]))
            {
                throw new UserException('Le champ \''.$field.'\' ne peut rester vide.');
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new UserException('Adresse e-mail invalide.');
        }

        if (!empty($data['code_postal']) && !preg_match('!^\d{5}$!', $data['code_postal']))
        {
            throw new UserException('Code postal invalide.');
        }

        if (!empty($data['passe']) && strlen($data['passe']) < 5)
        {
            throw new UserException('Le mot de passe doit faire au moins 5 caractères.');
        }

        return true;
    }

    public function add($data = array())
    {
        $this->_checkFields($data);

        if (!empty($data['passe']) && trim($data['passe']))
        {
            $data['passe'] = $this->_hashPassword($data['passe']);
        }

        if (!isset($data['id_categorie']))
        {
            $data['id_categorie'] = Garradin_Config::getInstance()->get('categorie_membres');
        }

        $db = Garradin_DB::getInstance();

        $db->simpleExec('INSERT INTO membres
            (id_categorie, passe, nom, email, adresse, code_postal, ville, pays, telephone,
            date_naissance, notes, date_inscription, date_connexion, date_cotisation)
            VALUES
            (:id_categorie, :passe, :nom, :email, :adresse, :code_postal, :ville, :pays, :telephone,
            :date_naissance, :notes, date(\'now\'), NULL, NULL);',
            $data);

        return $db->lastInsertRowId();
    }

    public function edit($id, $data = array())
    {
        $this->_checkFields($data);
        // UPDATE SQL
    }

    public function remove($id)
    {
    }

    public function search($query)
    {
    }

    public function getList($cat = 0, $page = 1)
    {
        $begin = ($page - 1) * self::ITEMS_PER_PAGE;

        $db = Garradin_DB::getInstance();

        $where = $cat ? 'WHERE id_categorie = '.(int)$cat : '';

        return $db->simpleStatementFetch(
            'SELECT id, id_categorie, nom, email, code_postal, ville, date_cotisation FROM membres '.$where.'
                ORDER BY nom LIMIT ?, ?;',
            SQLITE3_ASSOC,
            $begin,
            self::ITEMS_PER_PAGE
        );
    }
}

?>