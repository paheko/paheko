<?php

class Garradin_Config extends Garradin_DB
{
    const TYPE_CATEGORIE_COMPTA = 'categorie_compta';
    const TYPE_CATEGORIE_MEMBRE = 'categorie_membre';
    const TYPE_TEXTE = 'texte';
    const TYPE_NUMERIQUE = 'numerique';
    const TYPE_BOOL = 'bool';
    const TYPE_CHOIX = 'choix';

    protected function _initChamps()
    {
        $this->_ajoutChamp('asso_nom', self::TYPE_TEXTE, 'Mon asso');
        $this->_ajoutChamp('asso_adresse', self::TYPE_TEXTE, "42 rue des soupirs,\n21000 Dijon");
        $this->_ajoutChamp('asso_email', self::TYPE_TEXTE, "invalid@invalid.invalid");
        $this->_ajoutChamp('asso_site', self::TYPE_TEXTE, "http://example.tld/");

        $this->_ajoutChamp('email_expediteur', self::TYPE_TEXTE, "invalid@invalid.invalid");

        //$this->_ajoutChamp('membres_champs_obligatoires', self::TYPE_CHOIX_MULTIPLE, 'nom,

        $this->_ajoutChamp('compta_categorie_cotisations', self::TYPE_CATEGORIE_COMPTA, 1);
        $this->_ajoutChamp('compta_categorie_dons', self::TYPE_CATEGORIE_COMPTA, 2);
    }
}

?>