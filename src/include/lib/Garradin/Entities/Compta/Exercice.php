<?php

namespace Garradin\Entities\Compta;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Exercice extends Entity
{
    const TABLE = 'compta_exercices';

    protected $id;
    protected $libelle;
    protected $debut;
    protected $fin;
    protected $cloture = 0;

    protected $_types = [
        'id'      => 'integer',
        'libelle' => 'string',
        'debut'   => 'date',
        'fin'     => 'date',
        'cloture' => 'integer',
    ];

    protected $_validation_rules = [
        'libelle' => 'required|string|max:200',
        'debut'   => 'required|date|before:fin',
        'fin'     => 'required|date|after:debut',
        'cloture' => 'int|min:0|max:1',
    ];

    public function selfCheck(): void
    {
        parent::selfCheck();
        $this->assert($this->debut < $this->fin, 'La date de fin doit être postérieure à la date de début');
        $this->assert($this->cloture == 1 || !isset($this->_modified['cloture']), 'Il est interdit de réouvrir un exercice clôturé');

        $db = DB::getInstance();

        // Vérifier qu'on ne crée pas 2 exercices qui se recoupent
        if ($this->exists()) {
            $this->assert(
                !$db->test(self::TABLE, 'id != :id AND ((debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin))',
                    ['id' => $this->id(), 'debut' => $this->debut, 'fin' => $this->fin]),
                'La date de début ou de fin se recoupe avec un exercice existant.'
            );

            $this->assert(
                !$db->test(Mouvements::TABLE, 'id_exercice = ? AND date < ?', $this->id(), $this->debut),
                'Des mouvements de cet exercice ont une date antérieure à la date de début de l\'exercice.'
            );

            $this->assert(
                !$db->test(Mouvements::TABLE, 'id_exercice = ? AND date > ?', $this->id(), $this->fin),
                'Des mouvements de cet exercice ont une date postérieure à la date de fin de l\'exercice.'
            );
        }
        else {
            $this->assert(
                !$db->test(self::TABLE, '(debut <= :debut AND fin >= :debut) OR (debut <= :fin AND fin >= :fin)',
                    ['debut' => $this->debut, 'fin' => $this->fin]),
                'La date de début ou de fin se recoupe avec un exercice existant.'
            );
        }
    }

    public function close()
    {
        if ($this->cloture) {
            throw new \LogicException('Cet exercice est déjà clôturé');
        }

        $this->set('cloture', 1);
    }

    public function delete(): bool
    {
        $db = DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->test(Mouvements::TABLE, $db->where('id_exercice', $this->id())))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des mouvements y sont liés.');
        }

        return parent::delete();
    }
}
