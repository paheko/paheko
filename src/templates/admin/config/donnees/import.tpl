{include file="admin/_head.tpl" title="Import & export" current="config"}

{include file="admin/config/_menu.tpl" current="donnees"}

{include file="admin/config/donnees/_menu.tpl" current="import"}

<fieldset>
<dl>
	<dt>Membres</dt>
	<dd><a href="{$admin_url}membres/import.php">Import de la liste des membres</a></dd>
	<dd><a href="{$admin_url}membres/import.php?export=ods">Export de la liste des membres au format tableur Calc / Excel</a></dd>
	<dd><a href="{$admin_url}membres/import.php?export=csv">Export de la liste des membres au format CSV</a></dd>
	<dt>Comptabilité</dt>
	<dd><a href="{$admin_url}acc/years/import.php">Import des données comptables</a></dd>
	<dd><a href="{$admin_url}acc/years/import.php?export=ods">Export des données comptables au format tableur Calc / Excel</a></dd>
	<dd><a href="{$admin_url}acc/years/import.php?export=csv">Export des données comptables au format CSV</a></dd>
</dl>
</fieldset>

{include file="admin/_foot.tpl"}