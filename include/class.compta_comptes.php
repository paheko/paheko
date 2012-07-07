<?php

class Garradin_Compta_Comptes
{
    public function importPlan()
    {
        $plan = json_decode(file_get_contents(GARRADIN_ROOT . '/include/plan_comptable.json'), true);

        $db = Garradin_DB::getInstance();
        $db->exec('BEGIN;');
        $db->exec('DELETE FROM compta_comptes WHERE plan_comptable = 1;'); // Nettoyage avant insertion

        foreach ($plan as $id=>$compte)
        {
            $db->simpleInsert('compta_comptes', array(
                'id'        =>  $id,
                'parent'    =>  $compte['parent'],
                'libelle'   =>  $compte['nom'],
                'plan_comptable' => 1,
            ));
        }

        $db->exec('END;');

        return true;
    }

    public function add($data)
    {
        $this->_checkFields($data, true);

        $db = Garradin_DB::getInstance();

        $new_id = $data['parent'];
        $nb_sous_comptes = $db->simpleQuerySingle('SELECT COUNT(*) FROM compta_comptes WHERE parent = ?;');

        // Pas plus de 26 sous-comptes par compte, parce que l'alphabet s'arrête à 26 lettres
        if ($nb_sous_comptes >= 26)
        {
            throw new UserException('Nombre de sous-comptes maximal atteint pour ce compte parent-ci.');
        }

        $new_id .= chr(65+(int)$nb_sous_comptes);

        $db->simpleInsert('compta_comptes', array(
            'id'        =>  $new_id,
            'libelle'   =>  trim($data['libelle']),
            'parent'    =>  $data['parent'],
            'plan_comptable' => 0,
        ));

        return $new_id;
    }

    public function edit($id, $data)
    {
        $db = Garradin_DB::getInstance();

        // Vérification que l'on peut éditer ce compte
        if ($db->simpleQuerySingle('SELECT plan_comptable FROM compta_comptes WHERE id = ?;', false, $id))
        {
            throw new UserException('Ce compte fait partie du plan comptable et n\'est pas modifiable.');
        }

        $this->_checkFields($data);

        $db->simpleUpdate('compta_comptes', array('libelle' => trim($data['libelle'])),
            'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    public function delete($id)
    {
        $db = Garradin_DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal WHERE compte_debit = ? OR compte_debit = ? LIMIT 1;', false, $id, $id))
        {
            throw new UserException('Ce compte ne peut être supprimé car des opérations comptables y sont liées.');
        }

        $db->simpleExec('DELETE FROM compta_comptes WHERE id = ?;', $id);
        $db->simpleExec('DELETE FROM compta_comptes_bancaires WHERE id = ?;', $id);

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes WHERE id = ?;', true, $id);
    }

    public function getList($parent = 0)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetch('SELECT * FROM compta_comptes WHERE parent = ? ORDER BY id;', $parent);
    }

    public function listTree($parent = 0)
    {
        $db = Garradin_DB::getInstance();
        $parent = $parent ? 'WHERE parent LIKE \''.$db->escapeString($parent).'%\' ' : '';
        return $db->simpleStatementFetch('SELECT * FROM compta_comptes '.$parent.' ORDER BY id;');
    }

    protected function _checkFields(&$data, $force_parent_check = false)
    {
        $db = Garradin_DB::getInstance();

        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (isset($data['parent']) || $force_parent_check)
        {
            if (empty($data['parent']) && !trim($data['parent']))
            {
                throw new UserException('Le compte ne peut pas ne pas avoir de compte parent.');
            }

            if (!($id = $db->simpleQuerySingle('SELECT id FROM compta_comptes WHERE id = ? AND plan_comptable = 1;', false, $data['parent'])))
            {
                throw new UserException('Le compte parent indiqué n\'existe pas.');
            }

            $data['parent'] = $id;
        }

        return true;
    }
}

?>