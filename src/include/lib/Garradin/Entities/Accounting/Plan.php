<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Plan extends Entity
{
    const TABLE = 'acc_plans';

    protected $id;
    protected $label;
    protected $country;
    protected $code;

    protected $_types = [
        'id'      => 'integer',
        'label'   => 'string',
        'country' => 'string',
        'code'    => '?string',
    ];

    protected $_validation_rules = [
        'label'   => 'required|string|max:200',
        'country' => 'required|string|size:2',
        'code'    => 'string',
    ];

    public function selfCheck(): void
    {
        parent::selfCheck();
        $this->assert(Utils::getCountryName($this->country), 'Le code pays doit être un code ISO valide');
    }
}
