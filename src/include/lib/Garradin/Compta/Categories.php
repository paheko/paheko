<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

/**
 * Catégories comptables
 */
class Categories
{
    const DEPENSES = -1;
    const RECETTES = 1;
    const AUTRES = 0;

    public function importCategories()
    {
        $db = DB::getInstance();
        $db->import(\Garradin\ROOT . '/include/data/categories_comptables.sql');
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

        if (!$db->test('compta_comptes', $db->where('id', $data['compte'])))
        {
            throw new UserException('Le compte associé n\'existe pas.');
        }

        if (!isset($data['type']) ||
            ($data['type'] != self::DEPENSES && $data['type'] != self::RECETTES))
        {
            // Catégories "autres" pas possibles pour le moment
            throw new UserException('Type de catégorie inconnu.');
        }

        $db->insert('compta_categories', [
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

        $db->update('compta_categories',
            [
                'intitule'  =>  $data['intitule'],
                'description'=> $data['description'],
            ],
            'id = :id_select',
            ['id_select' => (int) $id]
        );

        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();

        $id = (int) $id;

        // Ne pas supprimer une catégorie qui est utilisée !
        if ($db->test('compta_journal', $db->where('id_categorie', $id)))
        {
            throw new UserException('Cette catégorie ne peut être supprimée car des opérations comptables y sont liées.');
        }

        $db->delete('compta_categories', 'id = ?', $id);

        return true;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->first('SELECT * FROM compta_categories WHERE id = ?;', (int)$id);
    }

    public function getList($type = null)
    {
        $db = DB::getInstance();
        $where = is_null($type) ? '1' : 'cat.type = '.(int)$type;

        $query = sprintf('SELECT cat.id, cat.*, cc.libelle AS compte_libelle
            FROM compta_categories AS cat INNER JOIN compta_comptes AS cc
                ON cc.id = cat.compte
            WHERE %s ORDER BY cat.intitule;', $where);

        return $db->getGrouped($query);
    }

    public function listMoyensPaiement()
    {
        $db = DB::getInstance();
        return $db->getGrouped('SELECT code, nom FROM compta_moyens_paiement ORDER BY nom COLLATE NOCASE;');
    }

    public function getMoyenPaiement($code)
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT nom FROM compta_moyens_paiement WHERE code = ?;', $code);
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
