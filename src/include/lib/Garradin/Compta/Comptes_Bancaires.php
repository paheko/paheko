<?php

namespace Garradin\Compta;

use \Garradin\DB;
use \Garradin\Utils;
use \Garradin\UserException;


class Comptes_Bancaires extends Comptes
{
    const NUMERO_PARENT_COMPTES = 512;

    public function add($data)
    {
        $db = DB::getInstance();

        $data['parent'] = self::NUMERO_PARENT_COMPTES;
        $data['id'] = null;

        $this->_checkBankFields($data);

        $new_id = parent::add($data);

        $db->simpleInsert('compta_comptes_bancaires', [
            'id'        =>  $new_id,
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ]);

        return $new_id;
    }

    public function edit($id, $data)
    {
        $db = DB::getInstance();

        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;', false, $id))
        {
            throw new UserException('Ce compte n\'est pas un compte bancaire.');
        }

        $this->_checkBankFields($data);
        $result = parent::edit($id, $data);

        if (!$result)
        {
            return $result;
        }

        $db->simpleUpdate('compta_comptes_bancaires', [
            'banque'    =>  $data['banque'],
            'iban'      =>  $data['iban'],
            'bic'       =>  $data['bic'],
        ], 'id = \''.$db->escapeString(trim($id)).'\'');

        return true;
    }

    /**
     * Supprime un compte bancaire
     * La suppression sera refusée si le compte est utilisé dans l'exercice en cours
     * ou dans une catégorie.
     * Le compte bancaire sera supprimé et le compte au plan comptable seulement désactivé
     * si le compte est utilisé dans un exercice précédent.
     *
     * La désactivation d'un compte fait qu'il n'est plus utilisable dans l'exercice courant
     * ou les exercices suivants, mais il est possible de le réactiver.
     * @param  string $id  Numéro du compte
     * @return boolean     TRUE si la suppression ou désactivation a été effectuée, une exception ou FALSE sinon
     */
    public function delete($id)
    {
        $db = DB::getInstance();
        if (!$db->simpleQuerySingle('SELECT 1 FROM compta_comptes_bancaires WHERE id = ?;', false, trim($id)))
        {
            throw new UserException('Ce compte n\'est pas un compte bancaire.');
        }

        // Ne pas supprimer/désactiver un compte qui est utilisé dans l'exercice courant
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_journal
                WHERE id_exercice = (SELECT id FROM compta_exercices WHERE cloture = 0 LIMIT 1) 
                AND (compte_debit = ? OR compte_debit = ?) LIMIT 1;', false, $id, $id))
        {
            throw new UserException('Ce compte ne peut être supprimé car des écritures y sont liées sur l\'exercice courant. '
                . 'Il faut supprimer ou ré-attribuer ces écritures avant de pouvoir supprimer le compte.');
        }

        // Il n'est pas possible de supprimer ou désactiver un compte qui est lié à des catégories
        if ($db->simpleQuerySingle('SELECT 1 FROM compta_categories WHERE compte = ? LIMIT 1;', false, $id))
        {
            throw new UserException('Ce compte ne peut être supprimé car des catégories y sont liées. '
                . 'Merci de supprimer ou modifier les catégories liées avant de le supprimer.');
        }

        $db->simpleExec('DELETE FROM compta_comptes_bancaires WHERE id = ?;', trim($id));

        try {
            $return = parent::delete($id);
        }
        catch (UserException $e) {
            // Impossible de supprimer car des opérations y sont encore liées
            // sur les exercices précédents, alors on le désactive
            $return = parent::disable($id);
        }

        return $return;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        return $db->simpleQuerySingle('SELECT * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc
            ON c.id = cc.id
            WHERE c.id = ?;', true, $id);
    }

    public function getList($parent = false)
    {
        $db = DB::getInstance();
        return $db->simpleStatementFetchAssocKey('SELECT c.id AS id, * FROM compta_comptes AS c
            INNER JOIN compta_comptes_bancaires AS cc ON c.id = cc.id
            WHERE c.parent = '.self::NUMERO_PARENT_COMPTES.' ORDER BY c.id;');
    }

    protected function _checkBankFields(&$data)
    {
        if (empty($data['banque']) || !trim($data['banque']))
        {
            throw new UserException('Le nom de la banque ne peut rester vide.');
        }

        if (empty($data['bic']))
        {
            $data['bic'] = '';
        }
        else
        {
            $data['bic'] = trim(strtoupper($data['bic']));
            $data['bic'] = preg_replace('![^\dA-Z]!', '', $data['bic']);

            if (!Utils::checkBIC($data['bic']))
            {
                throw new UserException('Code BIC/SWIFT invalide.');
            }
        }

        if (empty($data['iban']))
        {
            $data['iban'] = '';
        }
        else
        {
            $data['iban'] = trim(strtoupper($data['iban']));
            $data['iban'] = preg_replace('![^\dA-Z]!', '', $data['iban']);

            if (!Utils::checkIBAN($data['iban']))
            {
                throw new UserException('Code IBAN invalide.');
            }
        }

        return true;
    }
}