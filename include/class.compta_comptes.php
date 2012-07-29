<?php

class Garradin_Compta_Comptes
{
    const CAISSE = 530;

    const PASSIF = 0x01;
    const ACTIF = 0x02;
    const PRODUIT = 0x04;
    const CHARGE = 0x08;

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

        if (empty($data['id']))
        {
            $new_id = $data['parent'];
            $nb_sous_comptes = $db->simpleQuerySingle('SELECT COUNT(*) FROM compta_comptes WHERE parent = ?;', false, $new_id);

            // Pas plus de 26 sous-comptes par compte, parce que l'alphabet s'arrête à 26 lettres
            if ($nb_sous_comptes >= 26)
            {
                throw new UserException('Nombre de sous-comptes maximal atteint pour ce compte parent-ci.');
            }

            $new_id .= chr(65+(int)$nb_sous_comptes);
        }
        else
        {
            $new_id = $data['id'];
        }

        if (isset($data['position']))
        {
            $position = (int) $data['position'];
        }
        else
        {
            $classe = $new_id[0];

            if ($classe == 1)
            {
                if ($new_id == 11 || $new_id == 12)
                    $position = self::PASSIF | self::ACTIF;
                elseif ($new_id == 119 || $new_id == 129 || $new_id == 139)
                    $position = self::ACTIF;
                else
                    $position = self::PASSIF;
            }
            elseif ($classe == 2 || $classe == 3 || $classe == 5)
            {
                $position = self::ACTIF;
            }
            elseif ($classe == 4)
            {
                if (strlen($new_id) == 2)
                {
                    $position = self::PASSIF | self::ACTIF;
                }
                elseif (strlen($new_id) > 2)
                {
                    $prefixe = substr($new_id, 0, 3)

                    if ($prefixe == 401 || $prefixe == 411 || $prefixe == 421 || $prefixe == 430 || $prefixe == 440)
                    {
                    }
                }
            }
            elseif ($classe == 6)
            {
                $position = self::CHARGE;
            }
            elseif ($classe == 7)
            {
                $position = self::PRODUIT;
            }
            elseif ($classe == 8)
            {
                if (substr($new_id, 0, 2) == 86)
                    $position = self::CHARGE;
                elseif (substr($new_id, 0, 2) == 87)
                    $position = self::PRODUIT;
            }
        }

        $db->simpleInsert('compta_comptes', array(
            'id'        =>  $new_id,
            'libelle'   =>  trim($data['libelle']),
            'parent'    =>  $data['parent'],
            'plan_comptable' => 0,
            'position'  =>  $position,
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

        $db->simpleExec('DELETE FROM compta_comptes WHERE id = ?;', trim($id));

        return true;
    }

    public function get($id)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes WHERE id = ?;', true, trim($id));
    }

    public function getList($parent = 0)
    {
        $db = Garradin_DB::getInstance();
        return $db->simpleStatementFetch('SELECT * FROM compta_comptes WHERE parent = ? ORDER BY id;', $parent);
    }

    public function listTree($parent = 0, $include_children = true)
    {
        $db = Garradin_DB::getInstance();

        if ($include_children)
        {
            $parent = $parent ? 'WHERE parent LIKE \''.$db->escapeString($parent).'%\' ' : '';
        }
        else
        {
            $parent = $parent ? 'WHERE parent = \''.$db->escapeString($parent).'\' ' : 'WHERE parent = 0';
        }

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

        if (isset($data['id']))
        {
            $force_parent_check = true;
            $data['id'] = trim($data['id']);

            if ($db->simpleQuerySingle('SELECT 1 FROM compta_comptes WHERE id = ?;', false, $data['id']))
            {
                throw new UserException('Le compte numéro '.$data['id'].' existe déjà.');
            }
        }

        if (isset($data['parent']) || $force_parent_check)
        {
            if (empty($data['parent']) && !trim($data['parent']))
            {
                throw new UserException('Le compte ne peut pas ne pas avoir de compte parent.');
            }

            if (!($id = $db->simpleQuerySingle('SELECT id FROM compta_comptes WHERE id = ?;', false, $data['parent'])))
            {
                throw new UserException('Le compte parent indiqué n\'existe pas.');
            }

            $data['parent'] = trim($id);
        }

        if (isset($data['id']))
        {
            if (strncmp($data['id'], $data['parent'], strlen($data['parent'])) !== 0)
            {
                throw new UserException('Le compte '.$data['id'].' n\'est pas un sous-compte de '.$data['parent'].'.');
            }
        }

        return true;
    }
}

?>