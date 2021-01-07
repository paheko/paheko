<?php

namespace Garradin;

use KD2\Security;
use KD2\SMTP;
use Garradin\Membres\Session;

class Membres
{
    const DROIT_AUCUN = 0;
    const DROIT_ACCES = 1;
    const DROIT_ECRITURE = 2;
    const DROIT_ADMIN = 9;

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
                    $dt = \DateTime::createFromFormat('Y-m-d H:i', $data[$key]);
                    if (!$dt) {
                        throw new UserException(sprintf('Format invalide pour le champ "%s": AAAA-MM-JJ HH:mm attendu.', $config->title));
                    }
                    $data[$key] = $dt->format('Y-m-d H:i');
                }
                elseif ($config->type == 'date' && trim($data[$key]) !== '')
                {
                    $dt = \DateTime::createFromFormat('Y-m-d', $data[$key]);
                    if (!$dt) {
                        throw new UserException(sprintf('Format invalide pour le champ "%s": AAAA-MM-JJ attendu.', $config->title));
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
                    $data[$key] = strtolower($data[$key]);

                    if (trim($data[$key]) !== '' && !SMTP::checkEmailIsValid($data[$key], false))
                    {
                        throw new UserException(sprintf('Adresse email invalide "%s" pour le champ "%s".', $data[$key], $config->title));
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
                        throw new UserException('Le champs "%s" ne contient pas une valeur binaire.');
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
                $data['numero'] = $db->firstColumn('SELECT MAX(numero) + 1 FROM membres;') ?: 1;
            }
            elseif ($db->test('membres', $db->where('numero', $data['numero'])))
            {
                throw new UserException('Ce numéro de membre est déjà attribué à un autre membre.');
            }
        }

        $this->_checkFields($data, true, $require_password);

        if (isset($data[$id]) && $db->test('membres', $id . ' = ? COLLATE NOCASE', $data[$id]))
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

        if (empty($data['id_categorie']))
        {
            $data['id_categorie'] = Config::getInstance()->get('categorie_membres');
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
            && $db->firstColumn('SELECT 1 FROM membres WHERE '.$champ_id.' = ? COLLATE NOCASE AND id != ? LIMIT 1;', $data[$champ_id], (int)$id))
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

        if (!empty($data['passe']) && trim($data['passe']))
        {
            Session::checkPasswordValidity($data['passe']);
            $data['passe'] = Session::hashPassword($data['passe']);
        }
        else
        {
            unset($data['passe']);
        }

        if (isset($data['id_categorie']) && empty($data['id_categorie']))
        {
            $data['id_categorie'] = Config::getInstance()->get('categorie_membres');
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

        return self::_deleteMembres($ids);
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

    public function sendMessage(array $recipients, $subject, $message, $send_copy)
    {
        $config = Config::getInstance();

        foreach ($recipients as $key => $recipient)
        {
            // Ignorer les destinataires avec une adresse email vide
            if (empty($recipient->email))
            {
                unset($recipients[$key]);
                continue;
            }

            // Refuser d'envoyer un mail à une adresse invalide, sans vérifier le MX
            // sinon ça serait trop lent
            if (!SMTP::checkEmailIsValid($recipient->email, false))
            {
                throw new UserException(sprintf('Adresse email invalide : "%s". Aucun message n\'a été envoyé.', $recipient->email));
            }
        }

        if (!count($recipients)) {
        	throw new UserException('Aucun destinataire de la liste ne possède d\'adresse email.');
        }

        foreach ($recipients as $recipient)
        {
            Utils::sendEmail(Utils::EMAIL_CONTEXT_BULK, $recipient->email, $subject, $message, $recipient->id);
        }

        if ($send_copy)
        {
            Utils::sendEmail(Utils::EMAIL_CONTEXT_BULK, $config->get('email_asso'), $subject, $message);
        }

        return true;
    }

    public function listAllByCategory($id_categorie, $only_with_email = false)
    {
        $where = $only_with_email ? ' AND email IS NOT NULL' : '';
        return DB::getInstance()->get('SELECT id, email FROM membres WHERE id_categorie = ?' . $where, (int)$id_categorie);
    }

    public function listByCategory(?int $category_id): DynamicList
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

            if ($champs->isText($key)) {
                $columns[$key]['order'] = sprintf('transliterate_to_ascii(%s) COLLATE NOCASE %%s', $db->quoteIdentifier($key));
            }

        }

        $tables = 'membres';
        $conditions = $category_id ? sprintf('id_categorie = %d', $category_id) : sprintf('id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)');

        $order = $identity;

        if (!isset($columns[$order])) {
            $order = $champs->getFirstListed();
        }

        $list = new DynamicList($columns, $tables, $conditions);
        $list->orderBy($order, false);
        return $list;
    }

    public function countByCategory($cat = 0)
    {
        $db = DB::getInstance();

        $query = 'SELECT COUNT(*) FROM membres ';

        if (is_int($cat) && $cat)
        {
            $query .= sprintf('WHERE id_categorie = %d', $cat);
        }
        elseif (is_array($cat))
        {
            $query .= sprintf('WHERE id_categorie IN (%s)', implode(',', $cat));
        }

        $query .= ';';

        return $db->firstColumn($query);
    }

    public function countAllButHidden()
    {
        $db = DB::getInstance();
        return $db->firstColumn('SELECT COUNT(*) FROM membres WHERE id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1);');
    }

    static public function changeCategorie($id_cat, $membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;
        }

        $db = DB::getInstance();
        return $db->update('membres',
            ['id_categorie' => (int)$id_cat],
            sprintf('id IN (%s)', implode(',', $membres))
        );
    }

    static protected function _deleteMembres($membres)
    {
        foreach ($membres as &$id)
        {
            $id = (int) $id;

            // Suppression des fichiers liés
            $files = Fichiers::listLinkedFiles(Fichiers::LIEN_MEMBRES, $id, null);

            foreach ($files as $file)
            {
                $file = new Fichiers($file->id, $file);
                $file->remove();
            }
        }

        Plugin::fireSignal('membre.suppression', $membres);

        $db = DB::getInstance();

        // Suppression du membre
        return $db->delete('membres', $db->where('id', $membres));
    }
}
