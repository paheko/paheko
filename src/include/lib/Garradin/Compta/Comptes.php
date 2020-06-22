<?php

namespace Garradin\Compta;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Comptes
{
    const PASSIF = 0x01;
    const ACTIF = 0x02;
    const PRODUIT = 0x04;
    const CHARGE = 0x08;

    /**
     * Importe un plan comptable
     * @param  string $source_file Chemin du fichier à importer.
     * @param  boolean $delete_all True active la suppression des tous les anciens comptes (peu importe plan_comptable)
     * @return boolean/array Retourne un array des comptes non-supprimés avec leur raison, s'il y en a. Sinon true.
     *
     * Accepte 0 ou 1 argument : soit un chemin, soit true.
     * Sans arguments : importe le plan par défaut et ne supprime que les comptes
     * plus présent appartenants au plan d'origine (WHERE plan_comptable = 1)
     */
    public function importPlan($source_file = null, $delete_all = false)
    {
        $reset = false;

        if(null == $source_file)
        {
            $reset = true;
            $source_file = \Garradin\ROOT . '/include/data/plan_comptable.json';
        }

        $plan = json_decode(file_get_contents($source_file));

        if(is_null($plan))
        {
            throw new UserException('Le fichier n\'est pas du JSON ou n\'a pas pu être décodé.');
        }

        $db = DB::getInstance();
        $db->begin();
        $codes = [];

        foreach ($plan as $code=>$compte)
        {
            $codes[$code] = $db->firstColumn('SELECT id FROM compta_comptes WHERE code = ?;', $code);

            if (0 === $compte->parent) {
                $parent = null;
            }
            else {
                $parent = $db->firstColumn('SELECT id FROM compta_comptes WHERE code = ?;', $compte->parent);

                if (!$parent) {
                    throw new UserException(sprintf('Le compte parent "%s" n\'existe pas', $compte->parent));
                }
            }

            if ($codes[$code])
            {
                $db->update('compta_comptes', [
                    'parent'    =>  $parent,
                    'libelle'   =>  $compte->nom,
                    'position'  =>  $compte->position,
                    'plan_comptable' => $reset || !empty($compte->plan_comptable) ? 1 : 0,
                ], 'code = :code AND id_exercice IS NULL', ['code' => $code]);
            }
            else
            {
                $db->insert('compta_comptes', [
                    'code'      =>  $code,
                    'parent'    =>  $parent,
                    'libelle'   =>  $compte->nom,
                    'position'  =>  $compte->position,
                    'plan_comptable' => $reset || !empty($compte->plan_comptable) ? 1 : 0,
                    'id_exercice' => null,
                ]);

                $codes[$code] = $db->lastInsertRowId();
            }
        }

        // Effacer les comptes du plan comptable s'ils ne sont pas utilisés ailleurs
        // et qu'ils ne sont pas dans le nouveau plan comptable qu'on vient d'importer
        $sql = 'DELETE FROM compta_comptes WHERE id_exercice IS NULL AND id NOT IN (
            SELECT id FROM compta_comptes_bancaires
            UNION SELECT compte FROM compta_mouvements_lignes
            UNION SELECT compte FROM compta_categories)
            AND '. $db->where('code', 'NOT IN', array_keys($codes));

        // Si on ne fait qu'importer une mise à jour du plan comptable,
        // ne supprimer que les comptes qui n'ont pas été créés par l'usager
        if (!$delete_all) {
            $sql .= ' AND ' . $db->where('plan_comptable', 1);
        }

        $db->commit();

        return true;
    }

    public function exportPlan()
    {
        $name = 'plan_comptable';

        header('Content-type: application/json');
        header(sprintf('Content-Disposition: attachment; filename="%s.json"', $name));

        $liste = $this->listTree(0, true);

        $export = [];

        foreach ($liste as $k => $v)
        {
            $export[$v->id] = [
                'code'           => $v->id,
                'nom'            => $v->libelle,
                'parent'         => $v->parent,
                'position'       => $v->position,
                'plan_comptable' => $v->plan_comptable,
                'desactive'      => $v->desactive,
            ];
        }

        file_put_contents('php://output', json_encode($export, JSON_PRETTY_PRINT));

        return true;
    }

    public function add($data)
    {
        $this->_checkFields($data, true);

        $db = DB::getInstance();

        if (empty($data['id']))
        {
            $new_id = $data['parent'];
            $letters = range('A', 'Z');
            $sub_accounts = $db->getAssoc('SELECT id, id FROM compta_comptes 
                WHERE parent = ? ORDER BY id COLLATE NOCASE ASC;', $data['parent']);

            foreach ($letters as $letter)
            {
                if (!in_array($new_id . $letter, $sub_accounts))
                {
                    $new_id .= $letter;
                    break;
                }
            }

            // On a exaucé le nombre de sous-comptes possibles
            if ($new_id == $data['parent'])
            {
                throw new UserException('Nombre de sous-comptes maximal atteint pour ce compte parent-ci.');
            }
        }
        else
        {
            $new_id = strtoupper($data['id']);

            $parent = false;
            $id = $new_id;

            // Vérification que c'est bien le bon parent !
            // Sinon risque par exemple d'avoir parent = 5 et id = 512A !
            while (!$parent && strlen($id))
            {
                // On enlève un caractère à la fin jusqu'à trouver un compte parent
                $id = substr($id, 0, -1);
                $parent = $db->firstColumn('SELECT id FROM compta_comptes WHERE id = ?;', $id);
            }

            if (!$parent || $parent != $data['parent'])
            {
                throw new UserException('Le compte parent sélectionné est incorrect, par exemple pour créer un compte 512A il faut sélectionner 512 comme compte parent.');
            }
        }

        // Vérification que le compte n'existe pas déjà
        if ($db->test('compta_comptes', 'id = ?', $new_id))
        {
            throw new UserException('Ce numéro de compte existe déjà dans le plan comptable : ' . $new_id);
        }

        if (isset($data['position']))
        {
            $position = (int) $data['position'];
        }
        else
        {
            $position = $db->firstColumn('SELECT position FROM compta_comptes WHERE id = ?;', $data['parent']);
        }

        $db->insert('compta_comptes', [
            'id'        =>  $new_id,
            'libelle'   =>  trim($data['libelle']),
            'parent'    =>  $data['parent'],
            'plan_comptable' => 0,
            'position'  =>  (int)$position,
        ]);

        return $new_id;
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        $id = trim($id);

        // Vérification que l'on peut éditer ce compte
        if ($db->firstColumn('SELECT plan_comptable FROM compta_comptes WHERE id = ?;', $id))
        {
            throw new UserException('Ce compte fait partie du plan comptable et n\'est pas modifiable.');
        }

        if (isset($data['position']) && empty($data['position']))
        {
            throw new UserException('Aucune position du compte n\'a été indiquée.');
        }

        $this->_checkFields($data);

        $update = [
            'libelle'   =>  trim($data['libelle']),
        ];

        if (isset($data['position']))
        {
            $update['position'] = (int) trim($data['position']);
        }

        $db->update('compta_comptes', $update, $db->where('id', $id));

        return true;
    }

    public function delete($id)
    {
        $db = DB::getInstance();

        $id = trim($id);

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->test('compta_journal', 'compte_debit = ? OR compte_credit = ?', $id, $id))
        {
            throw new UserException('Ce compte ne peut être supprimé car des opérations comptables y sont liées.');
        }

        if ($db->test('compta_comptes_bancaires', $db->where('id', $id)))
        {
            throw new UserException('Ce compte ne peut être supprimé car il est lié à un compte bancaire.');
        }

        $db->delete('compta_comptes', $db->where('id', $id));

        return true;
    }

    /**
     * Peut-on supprimer ce compte ? (OUI s'il n'a pas d'écriture liée)
     * @param  string $id Numéro du compte
     * @return boolean TRUE si le compte n'a pas d'écriture liée
     */
    public function canDelete($id)
    {
        $db = DB::getInstance();

        $id = trim($id);

        if ($db->firstColumn('SELECT 1 FROM compta_journal
                WHERE compte_debit = ? OR compte_credit = ? LIMIT 1;', $id, $id))
        {
            return false;
        }

        if ($db->test('compta_categories', $db->where('compte', $id)))
        {
            return false;
        }

        return true;
    }

    /**
     * Peut-on désactiver ce compte ? (OUI s'il n'a pas d'écriture liée dans l'exercice courant)
     * @param  string $id Numéro du compte
     * @return boolean TRUE si le compte n'a pas d'écriture liée dans l'exercice courant
     */
    public function canDisable($id, &$code = 0)
    {
        $db = DB::getInstance();

        $id = trim($id);

        if ($db->firstColumn('SELECT 1 FROM compta_journal
                WHERE id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1) 
                AND (compte_debit = ? OR compte_credit = ?) LIMIT 1;', $id, $id))
        {
            $code = 1;
            return false;
        }

        if ($db->test('compta_categories', $db->where('compte', $id)))
        {
            $code = 2;
            return false;
        }

        return true;
    }

    /**
     * Désactiver un compte
     * Le compte ne sera plus utilisable pour les écritures ou les catégories mais restera en base de données
     * @param  string $id Numéro du compte
     * @return boolean TRUE si la désactivation a fonctionné, une exception utilisateur si
     * la désactivation n'est pas possible.
     */
    public function disable($id)
    {
        $db = DB::getInstance();

        $id = trim($id);

        if (!$this->canDisable($id, $code))
        {
            if ($code === 1)
            {
                throw new UserException('Ce compte ne peut être désactivé car des écritures y sont liées sur l\'exercice courant. '
                    . 'Il faut supprimer ou ré-attribuer ces écritures avant de pouvoir supprimer le compte.');
            }
            else
            {
                throw new UserException('Ce compte ne peut être désactivé car des catégories y sont liées.');
            }
        }

        return $db->update('compta_comptes', ['desactive' => 1], $db->where('id', $id));
    }

    /**
     * Renvoie si un compte existe et n'est pas désactivé
     * @param  string  $id Numéro de compte
     * @return boolean     TRUE si le compte existe et n'est pas désactivé
     */
    public function isActive($id)
    {
        $db = DB::getInstance();
        return $db->test('compta_comptes', $db->where('id', trim($id)) . ' AND ' . $db->where('desactive', '!=', 1));
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->first('SELECT * FROM compta_comptes WHERE id = ?;', trim($id));
    }

    public function getList($parent = 0)
    {
        $db = DB::getInstance();
        return $db->getGrouped('SELECT id, * FROM compta_comptes WHERE parent = ? ORDER BY id;', $parent);
    }

    public function getListAll()
    {
        $db = DB::getInstance();
        return $db->getAssoc('SELECT id, libelle FROM compta_comptes ORDER BY id;');
    }

    public function listTree($parent_id = 0, $include_children = true)
    {
        $db = DB::getInstance();

        if ($include_children && $parent_id)
        {
            $where = $db->where('parent', 'LIKE', $parent_id . '%');
        }
        elseif ($include_children && !$parent_id)
        {
            $where = '1';
        }
        else
        {
            $where = $db->where('parent', !$parent_id ? (int) $parent_id : (string) $parent_id);
        }

        $query = 'SELECT * FROM compta_comptes WHERE %s OR %s ORDER BY id;';
        $query = sprintf($query, $db->where('id', (string) $parent_id), $where);

        return $db->get($query);
    }

    protected function _checkFields(&$data, $force_parent_check = false)
    {
        $db = DB::getInstance();

        if (empty($data['libelle']) || !trim($data['libelle']))
        {
            throw new UserException('Le libellé ne peut rester vide.');
        }

        $data['libelle'] = trim($data['libelle']);

        if (isset($data['id']))
        {
            $force_parent_check = true;
            $data['id'] = trim($data['id']);

            if ($db->test('compta_comptes', $db->where('id', $data['id'])))
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

            if (!($id = $db->firstColumn('SELECT id FROM compta_comptes WHERE id = ?;', $data['parent'])))
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

    public function listSimpleTargetAccounts()
    {
        $accounts = DB::getInstance()->get('SELECT id, parent, label, b.label AS parent_label
            FROM acc_accounts a
            INNER JOIN acc_accounts b ON b.id = a.parent
            WHERE type != 0 ORDER BY type, parent, code;');

        $out = [];

        foreach ($accounts as $account) {
            if (!isset($out[$account->parent_label])) {
                $out[$account->parent_label] = [];
            }

            $out[$account->parent_label][$account->id] = $account->label;
        }

        return $out;
    }

    public function getPositions()
    {
        return [
            self::ACTIF     =>  'Actif',
            self::PASSIF    =>  'Passif',
            self::ACTIF | self::PASSIF      =>  'Actif ou passif (déterminé automatiquement au bilan selon le solde du compte)',
            self::CHARGE    =>  'Charge',
            self::PRODUIT   =>  'Produit',
            self::CHARGE | self::PRODUIT    =>  'Charge et produit',
        ];
    }
}
