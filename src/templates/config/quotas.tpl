{include file="_head.tpl" title="Utilisation de l'espace disque" current="config"}

{include file="config/_menu.tpl"}

<div class="short-block">
<h2 class="ruler">Base de données</h2>

<p class="help">
	{if FILE_STORAGE_BACKEND == 'SQLite'}
		La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, documents, etc.
	{else}
		La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, etc. <strong>sauf les documents</strong>.
	{/if}
</p>

<table class="list">
	<tr>
		<th>Total</th>
		<td>{size_meter tag="strong" total=$db_total value=$db_total}</td>
		<td></td>
	</tr>
	<tr>
		<th>Base de données seule</th>
		<td>{size_meter total=$db_total value=$db}</td>
		<td class="actions">{linkbutton shape="download" label="Faire une sauvegarde" href="!config/backup/"}
	</tr>
	<tr>
		<th>Sauvegardes</th>
		<td>{size_meter total=$db_total value=$db_backups}</td>
		<td class="actions">{linkbutton shape="menu" label="Liste des sauvegardes" href="!config/backup/restore.php"}
	</tr>
</table>

<h2 class="ruler">Documents</h2>

<table class="list">
	<tr>
		<th>Total</th>
		<td>{size_meter tag="strong" total=$quota_max value=$quota_used text="%s sur %s"}</td>
		<td>{$quota_left|size_in_bytes} libres</td>
		<td class="actions">{linkbutton shape="download" label="Sauvegarder les documents" href="!config/backup/documents.php"}
		</td>
	</tr>
	{foreach from=$contexts item="context" key="ctx"}
	<tr>
		<th>{$context.label}</th>
		<td>
			{size_meter total=$quota_used value=$context.size text="%s"}
		</td>
		<td></td>
		<td class="actions">
			{if $ctx == 'trash'}
				{linkbutton shape="trash" label="Voir les fichiers supprimés" href="!docs/trash.php"}
			{elseif $ctx == 'versions'}
				{linkbutton shape="reload" label="Nettoyer les anciennes versions" href="?prune=1"}
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
</div>

{include file="_foot.tpl"}