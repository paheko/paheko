<?php

namespace Paheko;

use Paheko\Accounting\Charts;
use Paheko\Users\DynamicFields;

Charts::updateInstalled('fr_pca_2018');
Charts::updateInstalled('fr_pcg_2014');
Charts::updateInstalled('fr_pcs_2018');
Charts::updateInstalled('fr_pcc_2020');
Charts::updateInstalled('fr_cse_2015');

// Datalist was not always in search table
DynamicFields::getInstance()->rebuildSearchTable();
