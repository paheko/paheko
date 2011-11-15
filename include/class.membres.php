<?php

class Garradin_Membres extends Garradin_DB
{
    const DROIT_CONNEXION = 1;
    const DROIT_INSCRIPTION = 2;

    const DROIT_WIKI_LIRE = 10;
    const DROIT_WIKI_ECRIRE = 11;
    const DROIT_WIKI_FICHIERS = 12;
    const DROIT_WIKI_ADMIN = 13;

    const DROIT_MEMBRE_AJOUTER = 20;
    const DROIT_MEMBRE_MODIFIER = 21;
    const DROIT_MEMBRE_LISTER = 22;
    const DROIT_MEMBRE_ADMIN = 23;

    const DROIT_COMPTA_GESTION = 30;
    const DROIT_COMPTA_ADMIN = 31;

    protected function _getSalt($length)
    {
        return implode('',
            array_rand(
                shuffle(str_split('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')),
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

    public function connexion($pseudo, $passe)
    {
    }
}

?>