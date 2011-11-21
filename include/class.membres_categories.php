<?php

require_once GARRADIN_ROOT . '/include/class.membres.php';

class Garradin_Membres_Categories
{
    public function add($data)
    {
        if (!isset($data['nom']) || !trim($data['nom']))
        {
            throw new UserException('Le nom ne peut rester vide.');
        }

        if (!isset($data['montant_cotisation']) || !is_numeric($data['montant_cotisation']))
        {
            throw new UserException('Le montant de cotisation doit être un chiffre.');
        }

        $db = Garradin_DB::getInstance();

        $db->simpleExec(
            'INSERT INTO membres_categories (nom, description, montant_cotisation, duree_cotisation) VALUES (?, ?, ?, ?);',
            $data['nom'],
            !empty($data['description']) ? trim($data['description']) : '',
            (float) $data['montant_cotisation'],
            12
        );

        return $db->lastInsertRowID();
    }

    public function edit($id, $data)
    {
        if (!isset($data['nom']) || !trim($data['nom']))
        {
            throw new UserException('Le nom ne peut rester vide.');
        }

        if (!isset($data['montant_cotisation']) || !is_numeric($data['montant_cotisation']))
        {
            throw new UserException('Le montant de cotisation doit être un chiffre.');
        }

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

    public function setAccess($cat)
    {
        for ($i = 1; $i <= func_num_args(); $i++)
        {
            $access = func_get_arg($i);
            if (!is_int($access))
            {
                throw new UnexpectedValueException($access . ' is not a valid access right');
            }

            $db = Garradin_DB::getInstance();
            $db->simpleExec('INSERT OR REPLACE INTO membres_categories_droits (id_categorie, droit) VALUES (?, ?);',
                (int)$cat,
                (int)$access);
        }

        return true;
    }
}

?>