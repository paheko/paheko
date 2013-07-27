{include file="admin/_head.tpl" title="Import & export" current="config"}

{include file="admin/config/_menu.tpl" current="import"}

<fieldset>
<dl>
	<dt>Membres</dt>
    <dd><a href="{$admin_url}membres/export.php">Export de la liste des membres en CSV (pour tableurs)</a></dd>
    <dt>Comptabilité</dt>
    <dd><a href="{$admin_url}compta/import.php">Import des données comptables</a></dd>
    <dd><a href="{$admin_url}compta/export.php">Export des données comptables en CSV</a></dd>
</dl>
</fieldset>

{include file="admin/_foot.tpl"}