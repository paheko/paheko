<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;

class Categories
{
    const DEPENSES = -1;
    const RECETTES = 1;
    const AUTRES = 0;

    public function importCategories()
    {
        $db = DB::getInstance();
        $db->exec(file_get_contents(\Garradin\ROOT . '/include/data/categories_comptables.sql'));
    }

    public function add($data)
    {
        $this->_checkFields($data);

        $db = DB::getInstance();

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
            ($data['type'] != self::DEPENSES && $data['type'] != self::RECETTES))
        {
            // Catégories "autres" pas possibles pour le moment
            throw new UserException('Type de catégorie inconnu.');
        }

        $db->simpleInsert('compta_categories', [
            'intitule'  =>  $data['intitule'],
            'description'=> $data['description'],
            'compte'    =>  $data['compte'],
            'type'      =>  (int)$data['type'],
        ]);

        return $db->lastInsertRowId();
    }

    public function edit($id, $data)
    {
        $this->_checkFields($data);

        $db = DB::getInstance();

        $db->simpleUpdate('compta_categories',
            [
                'intitule'  =>  $data['intitule'],
                'description'=> $data['description'],
            ],
            'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();

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
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_categories WHERE id = ?;', true, (int)$id);
    }

    public function getList($type = null)
    {
        $db = DB::getInstance();
        $type = is_null($type) ? '' : 'cat.type = '.(int)$type;
        return $db->simpleStatementFetchAssocKey('
            SELECT cat.id, cat.*, cc.libelle AS compte_libelle
            FROM compta_categories AS cat INNER JOIN compta_comptes AS cc
                ON cc.id = cat.compte
            WHERE '.$type.' ORDER BY cat.intitule;', SQLITE3_ASSOC);
    }

    public function listMoyensPaiement()
    {
        $db = DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT code, nom FROM compta_moyens_paiement ORDER BY nom COLLATE NOCASE;');
    }

    public function getMoyenPaiement($code)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT nom FROM compta_moyens_paiement WHERE code = ?;', false, $code);
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