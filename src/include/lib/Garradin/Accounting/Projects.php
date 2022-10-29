<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Project;
use KD2\DB\EntityManager;

class Projects
{
	public function listAssoc(): array
	{
		return $this->em->DB()->getAssoc($this->em->formatQuery('SELECT id, code || \' - \' || label FROM @TABLE ORDER BY code COLLATE NOCASE, label COLLATE U_NOCASE;'));
	}
}
