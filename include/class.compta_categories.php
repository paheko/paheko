<?php

class Garradin_Compta_Categories
{
    const DEPENSES = -1;
    const RECETTES = 1;
    const AUTRES = 0;

    public function add($data)
    {
        $this->_checkFields($data);

        $db = Garradin_DB::getInstance();

        if (empty($data['compte']) || !trim($data['compte']))
        {
            throw new UserException('Le compte associé ne peut rester vide.');
        }

        $data['compte'] = trim($data['compte']);

        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['compte']))
        {
            throw new UserException('Le compte associé n\'existe pas.');
        }

        if (!isset($data['type']) ||
            ($data['type'] != self::DEPENSES && $data['type'] != self::RECETTES
            && $data['type'] != self::AUTRES))
        {
            throw new UserException('Type de catégorie inconnu.');
        }

        $db->simpleInsert('compta_categories', array(
            'intitule'  =>  $data['intitule'],
            'description'=> $data['description'],
            'compte'    =>  $data['compte'],
            'type'      =>  (int)$data['type'],
        ));

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $this->_checkFields($data);

        $db = Garradin_DB::getInstance();

        $db->simpleUpdate('compta_categories',
            array(
                'intitule'  =>  $data['intitule'],
                'description'=> $data['description'],
            ),
            'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = Garradin_DB::getInstance();

        // Ne pas supprimer une catégorie qui est utilisée !
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE id_categorie = ? LIMIT 1;', false, $id))
        {
            throw new UserException('Cette catégorie ne peut être supprimée car des opérations comptables y sont liées.');
        }

        $db->simpleExec('DELETE FROM compta_categories WHERE id = ?;', $id);

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_categories WHERE id = ?;', true, (int)$id);
    }

    public function getList($type = null)
    {
        $db = Garradin_DB::getInstance();
        $type = is_null($type) ? '' : 'cat.type = '.(int)$type;
        return $db->simpleStatementFetch('
            SELECT cat.*, cc.libelle AS compte_libelle
            FROM compta_categories AS cat INNER JOIN compta_comptes AS cc
                ON cc.id = cat.compte
            WHERE '.$type.' ORDER BY cat.compte;', SQLITE3_ASSOC);
    }

    public function listMoyensPaiement()
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetch('SELECT * FROM compta_moyens_paiement ORDER BY nom COLLATE NOCASE;');
    }

    protected function _checkFields(&$data)
    {
        if (empty($data['intitule']) || !trim($data['intitule']))
        {
            throw new UserException('L\'intitulé ne peut rester vide.');
        }

        $data['intitule'] = trim($data['intitule']);
        $data['description'] = isset($data['description']) ? trim($data['description']) : '';

        return true;
    }
}

?>