<?php

class Garradin_Membres
{
    const DROIT_CONNEXION = 1;
    const DROIT_INSCRIPTION = 2;

    const DROIT_WIKI_LIRE = 10;
    const DROIT_WIKI_ECRIRE = 11;
    const DROIT_WIKI_FICHIERS = 12;
    const DROIT_WIKI_ADMIN = 13;

    const DROIT_MEMBRE_LISTER = 20;
    const DROIT_MEMBRE_GESTION = 21;
    const DROIT_MEMBRE_ADMIN = 22;

    const DROIT_COMPTA_GESTION = 30;
    const DROIT_COMPTA_ADMIN = 31;

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
        $db = Garradin_DB::getInstance();

        $_SESSION['logged_user'] = $user;
        $_SESSION['logged_user']['rights'] = $db->queryFetchAssoc('SELECT droit, droit FROM membres_categories_droits
            WHERE id_categorie = '.(int)$user['id_categorie'].';', SQLITE3_ASSOC);

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

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new UserException('Adresse e-mail \''.$field.'\' invalide.');
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

        return $db->simpleExec('INSERT INTO membres
            (id_categorie, passe, nom, pseudo, email, adresse, code_postal, ville, pays, telephone,
            date_anniversaire, details, date_inscription, date_connexion, date_cotisation)
            VALUES
            (:id_categorie, :passe, :nom, NULL, :email, :adresse, :code_postal, :ville, :pays, :telephone,
            :date_anniversaire, :details, date(\'now\'), NULL, NULL);',
            $data);
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

    public function getList($page = 1)
    {
    }

}

?>