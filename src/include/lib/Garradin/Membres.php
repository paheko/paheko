<?php

namespace Garradin;

use KD2\Security;
use KD2\SMTP;
use Garradin\Membres\Session;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Entities\Users\Email;

use Garradin\Users\Emails;
use Garradin\UserTemplate\UserTemplate;

class Membres
{
    const ITEMS_PER_PAGE = 50;

    // Gestion des données ///////////////////////////////////////////////////////

    public function _checkFields(&$data, $check_editable = true, $check_password = true)
    {
        $champs = Config::getInstance()->get('champs_membres');

        foreach ($champs->getAll() as $key=>$config)
        {
            if (!$check_editable && (!empty($config->private) || empty($config->editable)))
            {
                unset($data[$key]);
                continue;
            }

            if (!isset($data[$key]) || (!is_array($data[$key]) && trim($data[$key]) === '')
                || (is_array($data[$key]) && empty($data[$key])))
            {
                if (!empty($config->mandatory) && ($check_password || $key != 'passe'))
                {
                    $name = isset($config->title) ? $config->title : $key;
                    throw new UserException('Le champ "' . $name . '" doit obligatoirement être renseigné.');
                }
                elseif (!empty($config->mandatory))
                {
                    continue;
                }
            }

            if (isset($data[$key]))
            {
                if ($config->type == 'datetime' && trim($data[$key]) !== '')
                {
                    $value = sprintf('%s %s', $data[$key], $_POST[$key . '_time'] ?? '');
                    $dt = null;

                    if (preg_match('!^\d{2}/\d{2}/\d{4}\s\d{1,2}:\d{2}$!', $value)) {
                        $dt = \DateTime::createFromFormat('!d/m/Y H:i', $value);
                    }

                    if (!$dt) {
                        throw new UserException(sprintf('Format de date et heure invalide pour le champ "%s" : %s', $config->title, $value));
                    }

                    $data[$key] = $dt->format('Y-m-d H:i:s');
                }
                elseif ($config->type == 'date' && trim($data[$key]) !== '')
                {
                    $dt = \DateTime::createFromFormat('Y-m-d', $data[$key]);

                    if (!$dt) {
                        $dt = \DateTime::createFromFormat('d/m/y', $data[$key]);
                    }

                    if (!$dt) {
                        $dt = \DateTime::createFromFormat('d/m/Y', $data[$key]);
                    }

                    if (!$dt) {
                        throw new UserException(sprintf('Format invalide pour le champ "%s": AAAA-MM-JJ ou JJ/MM/AAAA attendu.', $config->title));
                    }
                    $data[$key] = $dt->format('Y-m-d');
                }
                elseif ($config->type == 'tel')
                {
                    $data[$key] = Utils::normalizePhoneNumber($data[$key]);
                }
                elseif ($config->type == 'country')
                {
                    $data[$key] = strtoupper(substr($data[$key], 0, 2));
                }
                elseif ($config->type == 'checkbox')
                {
                    $data[$key] = empty($data[$key]) ? 0 : 1;
                }
                elseif ($config->type == 'number' && trim($data[$key]) !== '')
                {
                    if (empty($data[$key]))
                    {
                        $data[$key] = 0;
                    }
                }
                elseif ($config->type == 'email')
                {
                    $data[$key] = strtolower(trim($data[$key]));

                    if (trim($data[$key]) !== '')
                    {
                        try {
                            Email::validateAddress($data[$key]);
                        }
                        catch (UserException $e) {
                            throw new UserException(sprintf('Champ "%s" : %s', $config->title, $e->getMessage()));
                        }
                    }
                }
                elseif ($config->type == 'select' && !in_array($data[$key], $config->options))
                {
                    throw new UserException('Le champ "' . $config->title . '" ne correspond pas à un des choix proposés.');
                }
                elseif ($config->type == 'multiple')
                {
                    if (empty($data[$key]))
                    {
                        $data[$key] = null;
                        continue;
                    }

                    if (is_array($data[$key]))
                    {
                        $binary = 0;

                        foreach ($data[$key] as $k => $v)
                        {
                            if (array_key_exists($k, $config->options) && !empty($v))
                            {
                                $binary |= 0x01 << $k;
                            }
                        }

                        $data[$key] = $binary;
                    }
                    elseif (!is_numeric($data[$key]) || $data[$key] < 0 || $data[$key] > PHP_INT_MAX)
                    {
                        throw new UserException(sprintf('Le champs "%s" ne contient pas une valeur binaire.', $key));
                    }
                }

                // Un champ texte vide c'est un champ NULL
                if (is_string($data[$key]) && trim($data[$key]) === '')
                {
                    $data[$key] = null;
                }
            }
        }

        return true;
    }

    public function add($data = [], $require_password = true)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();
        $id = $config->get('champ_identifiant');
        $champs = $config->get('champs_membres');

        // Numéro de membre
        if ($champs->get('numero'))
        {
            if (empty($data['numero']))
            {
                $data['numero'] = $db->firstColumn('SELECT MAX(CAST(numero AS INT)) + 1 FROM membres;');
            }
            elseif (!ctype_digit($data['numero'])) {
                throw new UserException(sprintf('Le numéro de membre "%s" n\'est pas valide : il ne doit contenir que des chiffres.', $data['numero']));
            }
            elseif ($db->test('membres', $db->where('numero', $data['numero'])))
            {
                throw new UserException('Ce numéro de membre est déjà attribué à un autre membre.');
            }
        }

        $this->_checkFields($data, true, $require_password);

        if (isset($data[$id]) && $db->test('membres', $id . ' = ? COLLATE U_NOCASE', $data[$id]))
        {
            throw new UserException('La valeur du champ '.$id.' est déjà utilisée par un autre membre, or ce champ doit être unique à chaque membre.');
        }

        if (isset($data['passe']) && trim($data['passe']) != '')
        {
            Session::checkPasswordValidity($data['passe']);
            $data['passe'] = Session::hashPassword($data['passe']);
        }
        else
        {
            unset($data['passe']);
        }

        if (empty($data['id_category']))
        {
            $data['id_category'] = Config::getInstance()->get('categorie_membres');
        }

        $db->insert('membres', $data);
        $id = $db->lastInsertRowId();

        Plugin::fireSignal('membre.nouveau', array_merge(['id' => $id], $data));

        return $id;
    }

    public function edit($id, $data = [], $check_editable = true)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        unset($data['id']);

        $this->_checkFields($data, $check_editable, false);
        $champ_id = $config->get('champ_identifiant');

        if (!empty($data[$champ_id])
            && $db->firstColumn('SELECT 1 FROM membres WHERE '.$champ_id.' = ? COLLATE U_NOCASE AND id != ? LIMIT 1;', $data[$champ_id], (int)$id))
        {
            throw new UserException('La valeur du champ '.$champ_id.' est déjà utilisée par un autre membre, or ce champ doit être unique à chaque membre.');
        }

        if (isset($data['numero']))
        {
            if (!preg_match('/^\d+$/', $data['numero']))
            {
                throw new UserException('Le numéro de membre ne doit contenir que des chiffres.');
            }
            elseif ($data['numero'] == 0)
            {
                throw new UserException('Le numéro de membre ne peut être vide.');
            }

            if ($db->test('membres', 'numero = ? AND id != ?', (int)$data['numero'], $id))
            {
                throw new UserException('Ce numéro est déjà attribué à un autre membre.');
            }
        }
        else {
            throw new UserException('Le numéro de membre est manquant.');
        }

        if (isset($data['delete_password'])) {
            $data['passe'] = null;
            unset($data['delete_password']);
        }
        elseif (!empty($data['passe']) && trim($data['passe']))
        {
            Session::checkPasswordValidity($data['passe']);
            $data['passe'] = Session::hashPassword($data['passe']);
        }
        else
        {
            unset($data['passe']);
        }

        if (isset($data['id_category']) && empty($data['id_category']))
        {
            $data['id_category'] = Config::getInstance()->get('categorie_membres');
        }

        if (empty($data))
        {
            return true;
        }

        $success = $db->update('membres', $data, $db->where('id', (int)$id));

        Plugin::fireSignal('membre.edit', $data);

        return $success;
    }

    public function get($id)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        return $db->first('SELECT *,
            '.$config->get('champ_identite').' AS identite,
            strftime(\'%s\', date_inscription) AS date_inscription,
            strftime(\'%s\', date_connexion) AS date_connexion
            FROM membres WHERE id = ? LIMIT 1;', (int)$id);
    }

    public function delete($ids)
    {
        if (!is_array($ids))
        {
            $ids = [(int)$ids];
        }

        $session = Session::getInstance();

        if ($session->isLogged())
        {
            $user = $session->getUser();

            foreach ($ids as $id)
            {
                if ($user->id == $id)
                {
                    throw new UserException('Il n\'est pas possible de supprimer son propre compte.');
                }
            }
        }

        return $this->_deleteMembres($ids);
    }

    public function getNom($id)
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        return $db->firstColumn('SELECT '.$config->get('champ_identite').' FROM membres WHERE id = ? LIMIT 1;', (int)$id);
    }

    public function getIDWithNumero($numero)
    {
        return DB::getInstance()->firstColumn('SELECT id FROM membres WHERE numero = ?;', (int) $numero);
    }

    public function quickSearch(string $query)
    {
        $identity = Config::getInstance()->get('champ_identite');
        $operator = 'LIKE';

        if (is_numeric(trim($query)))
        {
            $column = 'numero';
            $operator = '= ?';
        }
        elseif (strpos($query, '@') !== false)
        {
            $column = 'email';
        }
        else
        {
            $column = $identity;
        }

        if ($operator == 'LIKE') {
            $query = '%' . str_replace(['%', '_'], '\\', $query) . '%';
            $operator = 'LIKE ? ESCAPE \'\\\'';
        }

        $sql = sprintf('SELECT id, numero, %s AS identite FROM membres WHERE %s %s ORDER BY %1$s LIMIT 50;', $identity, $column, $operator);
        return DB::getInstance()->get($sql, $query);
    }

    public function listAllButHidden(): array
    {
        return DB::getInstance()->get('SELECT * FROM membres
            WHERE id_category IN (SELECT id FROM users_categories WHERE hidden = 0)
                AND email IS NOT NULL AND email != \'\';');
    }

    public function listAllByCategory($id_category, $only_with_email = false)
    {
        $where = $only_with_email ? ' AND email IS NOT NULL' : '';
        return DB::getInstance()->get('SELECT * FROM membres WHERE id_category = ?' . $where, (int)$id_category);
    }

    public function listByCategory(?int $id_category): DynamicList
    {
        $config = Config::getInstance();
        $db = DB::getInstance();
        $identity = $config->get('champ_identite');
        $champs = $config->get('champs_membres');

        $columns = [
            '_user_id' => [
                'select' => 'id',
            ],
            'numero' => [
                'label' => 'Num.',
            ],
        ];

        $fields = $champs->getListedFields();

        foreach ($fields as $key => $config) {
            if (isset($columns[$key])) {
                continue;
            }

            $columns[$key] = [
                'label' => $config->title
            ];
        }

        $tables = 'membres';
        $conditions = $id_category ? sprintf('id_category = %d', $id_category) : sprintf('id_category IN (SELECT id FROM users_categories WHERE hidden = 0)');

        $order = $identity;

        if (!isset($columns[$order])) {
            $order = $champs->getFirstListed();
        }

        $list = new DynamicList($columns, $tables, $conditions);
        if ($order) {
            $list->orderBy($order, false);
        }
        return $list;
    }

    public function countByCategory($cat = 0)
    {
        $db = DB::getInstance();

        $query = 'SELECT COUNT(*) FROM membres ';

        if (is_int($cat) && $cat)
        {
            $query .= sprintf('WHERE id_category = %d', $cat);
        }
        elseif (is_array($cat))
        {
            $query .= sprintf('WHERE id_category IN (%s)', implode(',', $cat));
        }

        $query .= ';';

        return $db->firstColumn($query);
    }

    public function countAllButHidden()
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT COUNT(*) FROM membres WHERE id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1);');
    }

    public function getAttachementsDirectory(int $id)
    {
        return File::CONTEXT_USER . '/' . $id;
    }

    static public function changeCategorie($id_cat, $membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        $db = DB::getInstance();
        return $db->update('membres',
            ['id_category' => (int)$id_cat],
            sprintf('id IN (%s)', implode(',', $membres))
        );
    }

    protected function _deleteMembres(array $membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;

            Files::delete($this->getAttachementsDirectory($id));
        }

        Plugin::fireSignal('membre.suppression', $membres);

        $db = DB::getInstance();

        // Suppression du membre
        return $db->delete('membres', $db->where('id', $membres));
    }
}
