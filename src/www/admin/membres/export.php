<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$export = new Membres\Export;


if(isset($_POST["format"]))
{
  if ($_POST["format"] === Membres\Export::TYPE_CSV)
  {
      $export->toCSV();
      exit;
  }
  elseif ($_POST["format"] === Membres\Export::TYPE_ODS)
  {
      $export->toODS();
      exit;
  }
  else
  {
    throw new ValidationException('Format inconnu');
    exit;
  }
}

$csrf_key = 'membres_export';

$tpl->assign(compact('csrf_key'));

$tpl->display('admin/membres/export.tpl');
