{include file="_head.tpl" title="Utilisation de l'espace disque" current="config"}

{include file="config/_menu.tpl"}

{if $_GET.msg === 'PRUNED'}
	<p class="confirm block">Les anciennes versions des fichiers qui étaient trop anciennes ont bien été supprimées.</p>
{/if}

<h2 class="ruler">Base de données</h2>

<div class="center-block">
	<p class="help">
		{if FILE_STORAGE_BACKEND == 'SQLite'}
			La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, documents, etc.
		{else}
			La base de données stocke toutes les informations&nbsp;: membres, activités, rappels, comptabilité, site web, etc. <strong>sauf les documents</strong>.
		{/if}
	</p>
</div>

<table class="list meter-map auto" style="--size: 20em">
	<tr>
		<th>Total</th>
		<td class="size"><nobr>{$db_total|size_in_bytes}</nobr></td>
		<td></td>
	</tr>
	<tr height="{$db|percent_of:$db_total}%">
		<th>Base de données seule</th>
		<td class="size"><nobr>{$db|size_in_bytes}</nobr></td>
		<td class="actions">{linkbutton shape="download" label="Faire une sauvegarde" href="!config/backup/"}
	</tr>
	<tr height="{$db_backups|percent_of:$db_total}%">
		<th>Sauvegardes</th>
		<td class="size"><nobr>{$db_backups|size_in_bytes}</nobr></td>
		<td class="actions">{linkbutton shape="menu" label="Liste des sauvegardes" href="!config/backup/restore.php"}
	</tr>
</table>

<h2 class="ruler">Fichiers</h2>

<div class="center-block">
	<p>{$quota_used|size_in_bytes} utilisés sur {$quota_max|size_in_bytes} autorisés <strong>({$quota_left|size_in_bytes} libres)</strong></p>
</div>

<form method="post" action="">
	<table class="list meter-map auto" style="--size: 45em">
		<tr>
			<th>Total</th>
			<td class="size"><nobr>{$quota_used|size_in_bytes}</nobr></td>
			<td class="actions">{linkbutton shape="download" label="Sauvegarder les fichiers" href="!config/backup/documents.php"}
			</td>
		</tr>
		{foreach from=$contexts item="context" key="ctx"}
		<tr height="{$context.size|percent_of:$quota_used}%">
			<th>{$context.label}</th>
			<td class="size"><nobr>{$context.size|size_in_bytes}</nobr></td>
			<td class="actions">
				{if $ctx == 'trash'}
					{linkbutton shape="trash" label="Voir les fichiers supprimés" href="!docs/trash.php"}
				{elseif $ctx == 'versions' && $versioning_policy !== 'none'}
					{button type="submit" name="prune_versions" value=1 shape="reload" label="Nettoyer les anciennes versions"}
				{elseif $ctx == 'versions' && $versioning_policy === 'none' && $context.size}
					{linkbutton href="?prune_versions=1" shape="delete" label="Supprimer les anciennes versions" target="_dialog"}
				{/if}
			</td>
		</tr>
		{/foreach}
	</table>
</form>

{include file="_foot.tpl"}