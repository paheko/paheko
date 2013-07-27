<?php

namespace Garradin;

class Membres_Categories
{
    protected $droits = array(
        'inscription'=> Membres::DROIT_AUCUN,
        'connexion' =>  Membres::DROIT_ACCES,
        'membres'   =>  Membres::DROIT_ACCES,
        'compta'    =>  Membres::DROIT_ACCES,
        'wiki'      =>  Membres::DROIT_ACCES,
        'config'    =>  Membres::DROIT_AUCUN,
    );

    static public function getDroitsDefaut()
    {
        return $this->droits;
    }

    protected function _checkData(&$data)
    {
        if (!isset($data['nom']) || !trim($data['nom']))
        {
            throw new UserException('Le nom de catégorie ne peut rester vide.');
        }

        if (!isset($data['montant_cotisation']) || !is_numeric($data['montant_cotisation']))
        {
            throw new UserException('Le montant de cotisation doit être un chiffre.');
        }

        if (isset($data['montant_cotisation']))
        {
            $data['montant_cotisation'] = (float)$data['montant_cotisation'];
        }
        else
        {
            $data['montant_cotisation'] = 0;
        }

        if (isset($data['duree_cotisation']))
        {
            $data['duree_cotisation'] = (int)$data['duree_cotisation'];
        }
        else
        {
            $data['duree_cotisation'] = 0;
        }
    }

    public function add($data)
    {
        $this->_checkData($data);

        if (!isset($data['duree_cotisation']) || !is_numeric($data['duree_cotisation']))
        {
            $data['duree_cotisation'] = 12;
        }

        if (!isset($data['description']))
        {
            $data['description'] = '';
        }

        foreach ($this->droits as $key=>$value)
        {
            if (!isset($data['droit_'.$key]))
                $data['droit_'.$key] = $value;
            else
                $data['droit_'.$key] = (int)$data['droit_'.$key];
        }

        $db = DB::getInstance();
        $db->simpleInsert('membres_categories', $data);

        return $db->lastInsertRowID();
    }

    public function edit($id, $data)
    {
        $this->_checkData($data);

        foreach ($this->droits as $key=>$value)
        {
            if (isset($data['droit_'.$key]))
                $data['droit_'.$key] = (int)$data['droit_'.$key];
        }

        if (!isset($data['cacher']) || $data['cacher'] != 1)
            $data['cacher'] = 0;

        $db = DB::getInstance();
        return $db->simpleUpdate('membres_categories', $data, 'id = '.(int)$id);
    }

    public function get($id)
    {
        $db = DB::getInstance();

        return $db->simpleQuerySingle('SELECT * FROM membres_categories WHERE id = ?;',
            true, (int) $id);
    }

    public function remove($id)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        if ($id == $config->get('categorie_membres'))
        {
            throw new UserException('Il est interdit de supprimer la catégorie définie par défaut dans la configuration.');
        }

        if ($db->simpleQuerySingle('SELECT 1 FROM membres WHERE id_categorie = ?;', false, (int)$id))
        {
            throw new UserException('La catégorie contient encore des membres, il n\'est pas possible de la supprimer.');
        }

        $db->simpleUpdate(
            'wiki_pages',
            array(
                'droit_lecture'     =>  Wiki::LECTURE_NORMAL,
                'droit_ecriture'    =>  Wiki::ECRITURE_NORMAL,
            ),
            'droit_lecture = '.(int)$id.' OR droit_ecriture = '.(int)$id
        );

        return $db->simpleExec('DELETE FROM membres_categories WHERE id = ?;', (int) $id);
    }

    public function listSimple()
    {
        $db = DB::getInstance();
        return $db->queryFetchAssoc('SELECT id, nom FROM membres_categories ORDER BY nom;');
    }

    public function listComplete()
    {
        $db = DB::getInstance();
        return $db->queryFetch('SELECT * FROM membres_categories ORDER BY nom;');
    }

    public function listCompleteWithStats()
    {
        $db = DB::getInstance();
        return $db->queryFetch('SELECT *, (SELECT COUNT(*) FROM membres WHERE id_categorie = membres_categories.id) AS nombre FROM membres_categories ORDER BY nom;');
    }


    public function listHidden()
    {
        $db = DB::getInstance();
        return $db->queryFetchAssoc('SELECT id, nom FROM membres_categories WHERE cacher = 1;');
    }

    public function listNotHidden()
    {
        $db = DB::getInstance();
        return $db->queryFetchAssoc('SELECT id, nom FROM membres_categories WHERE cacher = 0;');
    }
}

?>