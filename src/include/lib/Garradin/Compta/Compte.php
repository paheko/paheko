<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Compte extends Entity
{
    const PASSIF = 1;
    const ACTIF = 2;
    const ACTIF_PASSIF = 3;

    const PRODUIT = 4;
    const CHARGE = 5;
    const PRODUIT_CHARGE = 6;

    const BANQUE = 7;
    const CAISSE = 8;

    const CHEQUE_A_ENCAISSER = 9;
}