{include file="_head.tpl" title="Utilisation de l'espace disque" current="config"}

{include file="config/_menu.tpl"}

<div class="center-block short-block">
<h2 class="ruler">Base de données</h2>

<p class="help">
	{if FILE_STORAGE_BACKEND == 'SQLite'}
		La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, documents, etc.
	{else}
		La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, etc. <strong>sauf les documents</strong>.
	{/if}
</p>

<dl class="describe">
	<dt>Base de données</dt>
	<dd>{size_meter total=$db_total value=$db}</dd>
	<dt>Sauvegardes</dt>
	<dd>{size_meter total=$db_total value=$db_backups}</dd>
	<dt>Total</dt>
	<dd>{size_meter tag="strong" total=$db_total value=$db_total}</dd>
</dl>

<h2 class="ruler">Documents</h2>

<dl class="describe">
	<dt>Total</dt>
	<dd>{size_meter tag="strong" total=$quota_max value=$quota_used text="%s sur %s"} ({$quota_left|size_in_bytes} libres)</dd>
	{foreach from=$contexts item="context"}
		<dt>{$context.label}</dt>
		<dd>{size_meter total=$quota_used value=$context.size text="%s"}</dd>
	{/foreach}
</dl>
</div>

{include file="_foot.tpl"}