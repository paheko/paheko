<?php

require_once GARRADIN_ROOT . '/include/class.membres.php';

class Garradin_Membres_Categories
{
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

        if (!isset($data['duree_cotisation']) || !is_numeric($data['duree_cotisation']))
        {
            $data['duree_cotisation'] = 12;
        }

        if (!isset($data['description']))
        {
            $data['description'] = '';
        }

        $droits = array(
            'wiki'      =>  Garradin_Membres::DROIT_ACCES,
            'membres'   =>  Garradin_Membres::DROIT_ACCES,
            'compta'    =>  Garradin_Membres::DROIT_ACCES,
            'inscription'=> Garradin_Membres::DROIT_ACCES,
            'connexion' =>  Garradin_Membres::DROIT_ACCES,
        );

        foreach ($droits as $key=>$value)
        {
            if (!isset($data['droit_'.$key]))
                $data['droit_'.$key] = $value;
            else
                $data['droit_'.$key] = (int)$data['droit_'.$key];
        }
    }

    public function add($data)
    {
        $this->_checkData($data);

        $db = Garradin_DB::getInstance();
        $db->simpleInsert('membres_categories', $data);

        return $db->lastInsertRowID();
    }

    public function edit($id, $data)
    {
        $this->_checkData($data);
        $db = Garradin_DB::getInstance();

        return $db->simpleExec(
            'UPDATE membres_categories SET nom = ?, description = ?, montant_cotisation = ?, duree_cotisation = ?) WHERE id = ?',
            $data['nom'],
            !empty($data['description']) ? trim($data['description']) : '',
            (float) $data['montant_cotisation'],
            12,
            (int) $id
        );
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();

        return $db->simpleQuerySingle('SELECT * FROM membres_categories WHERE id = ?;',
            true, (int) $id);
    }

    public function remove($id)
    {
        $db = Garradin_DB::getInstance();

        if ($db->simpleQuerySingle('SELECT 1 FROM membres WHERE id_categorie = ?;', false, (int)$id))
        {
            throw new UserException('La catégorie contient encore des membres, il n\'est pas possible de la supprimer.');
        }

        return $db->simpleExec('DELETE FROM membres_categories WHERE id = ?;', (int) $id);
    }

    public function listSimple()
    {
        $db = Garradin_DB::getInstance();
        return $db->queryFetchAssoc('SELECT id, nom FROM membres_categories ORDER BY nom;');
    }
}

?>