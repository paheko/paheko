<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Entity;

/**
 * Catégories comptables
 */
class Categorie extends Entity
{
    const DEPENSE = 1;
    const RECETTE = 2;
    const VIREMENT = 3;

    const DETTE_ADHERENT = 5;
    const DETTE_FOURNISSEUR = 6;
    const CREANCE_ADHERENT = 7;
    const CREANCE_FOURNISSEUR = 8;

    protected $id;
    protected $type;
    protected $intitule;
    protected $description;

    protected $compte;
}
