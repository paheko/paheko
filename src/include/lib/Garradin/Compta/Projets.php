<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\UserException;

class Projets
{
    public function getAssocList()
    {
        return DB::getInstance()->getAssoc('SELECT id, libelle FROM compta_projets ORDER BY libelle;');
    }

    public function getList()
    {
        return DB::getInstance()->get('SELECT *, 
            (SELECT COUNT(*) FROM compta_journal WHERE id_projet = compta_projets.id) AS nb_operations
            FROM compta_projets ORDER BY libelle;');
    }

    public function get($id)
    {
        return DB::getInstance()->first('SELECT * FROM compta_projets WHERE id = ?;', (int) $id);
    }

    public function add($libelle)
    {
        if (trim($libelle) == '')
        {
            throw new UserException('Le libellé est obligatoire');
        }

        $db = DB::getInstance();

        $db->insert('compta_projets', ['libelle' => trim($libelle)]);

        return $db->lastInsertRowId();
    }

    public function edit($id, $libelle)
    {
        if (trim($libelle) == '')
        {
            throw new UserException('Le libellé est obligatoire');
        }

        $db = DB::getInstance();

        return $db->update('compta_projets', ['libelle' => trim($libelle)], $db->where('id', (int) $id));
    }

    public function remove($id)
    {
        $db = DB::getInstance();

        return $db->delete('compta_projets', $db->where('id', (int) $id));
    }
}
