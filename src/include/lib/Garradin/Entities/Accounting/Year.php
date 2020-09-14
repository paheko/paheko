<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;
use Garradin\Entity;
use Garradin\DB;
use Garradin\UserException;

class Year extends Entity
{
    const TABLE = 'acc_years';

    protected $id;
    protected $label;
    protected $start_date;
    protected $end_date;
    protected $closed = 0;
    protected $id_chart;

    protected $_types = [
        'id'         => 'integer',
        'label'      => 'string',
        'start_date' => 'date',
        'end_date'   => 'date',
        'closed'     => 'integer',
        'id_chart'   => 'integer',
    ];

    protected $_form_rules = [
        'label'      => 'required|string|max:200',
        'start_date' => 'required|date|before:end_date',
        'end_date'   => 'required|date|after:start_date',
    ];

    public function selfCheck(): void
    {
        parent::selfCheck();
        $this->assert($this->start_date < $this->end_date, 'La date de fin doit être postérieure à la date de début');
        $this->assert($this->closed === 0 || $this->closed === 1);
        $this->assert($this->closed == 1 || !isset($this->_modified['closed']), 'Il est interdit de réouvrir un exercice clôturé');

        $db = DB::getInstance();

        $this->assert($this->id_chart !== null);

        // Vérifier qu'on ne crée pas 2 exercices qui se recoupent
        if ($this->exists()) {
            $this->assert(
                !$db->test(self::TABLE, 'id != :id AND ((start_date <= :start_date AND end_date >= :start_date) OR (start_date <= :end_date AND end_date >= :start_date))',
                    ['id' => $this->id(), 'start_date' => $this->start_date, 'end_date' => $this->end_date]),
                'La date de début ou de fin se recoupe avec un exercice existant.'
            );

            $this->assert(
                !$db->test(Transaction::TABLE, 'id_year = ? AND date < ?', $this->id(), $this->start_date),
                'Des mouvements de cet exercice ont une date antérieure à la date de début de l\'exercice.'
            );

            $this->assert(
                !$db->test(Transaction::TABLE, 'id_year = ? AND date > ?', $this->id(), $this->end_date),
                'Des mouvements de cet exercice ont une date postérieure à la date de fin de l\'exercice.'
            );
        }
        else {
            $this->assert(
                !$db->test(self::TABLE, '(start_date <= :start_date AND end_date >= :start_date) OR (start_date <= :end_date AND end_date >= :start_date)',
                    ['start_date' => $this->start_date, 'end_date' => $this->end_date]),
                'La date de début ou de fin se recoupe avec un exercice existant.'
            );
        }
    }

    public function close()
    {
        if ($this->closed) {
            throw new \LogicException('Cet exercice est déjà clôturé');
        }

        $this->set('closed', 1);
    }

    public function delete(): bool
    {
        $db = DB::getInstance();

        // Ne pas supprimer un compte qui est utilisé !
        if ($db->test(Transaction::TABLE, $db->where('id_year', $this->id())))
        {
            throw new UserException('Cet exercice ne peut être supprimé car des mouvements y sont liés.');
        }

        return parent::delete();
    }

    public function chart()
    {
        return EntityManager::findOneById(Chart::class, $this->id_chart);
    }
}
