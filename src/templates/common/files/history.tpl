{include file="_head.tpl" title="Historique" current="docs"}

<h2 class="ruler">{$file.name} — Historique</h2>

{form_errors}

{if $_GET.msg == 'RENAMED'}
<p class="confirm block">
	La version a été nommée.
</p>
{elseif $_GET.msg == 'DELETED'}
<p class="confirm block">
	La version a été supprimée.
</p>
{elseif $_GET.msg == 'RESTORED'}
<p class="confirm block">
	La version a été restaurée.
</p>
{/if}

<p class="help">
	Voici la liste des anciennes versions de ce fichier.
</p>

<form method="post" action="">
	<table class="list">
		<thead>
			<tr>
				<td class="num">Version</td>
				<td>Nom</td>
				<td>Date</td>
				<td class="num">Taille</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="num"></td>
				<th>Version actuelle</th>
				<td>{$file.modified|relative_date:true}</td>
				<td class="size">{$file.size|size_in_bytes:true}</td>
				<td class="actions">
				</td>
			</tr>
			{foreach from=$versions item="v"}
			<tr>
				<td class="num">{$v.version}</td>
				<th>{$v.name}</th>
				<td>{$v.date|relative_date:true}</td>
				<td class="size">{$v.size|size_in_bytes:true}</td>
				<td class="actions">
					{if $file->canDelete()}
						{button shape="delete" label="Supprimer cette version" name="delete" value=$v.version type="submit"}
					{/if}
					{button shape="history" label="Restaurer" name="restore" value=$v.version type="submit"}
					{linkbutton shape="edit" label="Nommer" href="?p=%s&rename=%d"|args:$file->path_uri():$v.version}
					{linkbutton shape="download" label="Télécharger" href="?p=%s&download=%d"|args:$file->path_uri():$v.version target="_blank"}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
	{csrf_field key=$csrf_key}

	<details>
		<summary class="help block">
			Les anciennes versions sont supprimées automatiquement, sauf pour les <strong>versions nommées</strong> qui sont conservées.
		</summary>
		<div class="help block">
			<p>Les anciennes versions sont supprimées automatiquement selon ces règles&nbsp;:</p>
			<ul>
			{if (FILE_VERSIONING_POLICY ?? $config.file_versioning_policy) === 'min'}
				<li>Dans les 10 premières minutes, on conserve une version&nbsp;;</li>
				<li>Dans l'heure suivante, on conserve une version&nbsp;;</li>
				<li>Dans les 24h suivantes, on conserve une version&nbsp;;</li>
				<li>Dans les 2 mois suivants, on conserve une version&nbsp;;</li>
				<li>Ensuite, on conserve une seule version.</li>
			{elseif (FILE_VERSIONING_POLICY ?? $config.file_versioning_policy) === 'avg'}
				<li>Dans les 10 premières minutes, on conserve une version toutes les 5 minutes&nbsp;;</li>
				<li>Dans l'heure suivante, on conserve une version toutes les 15 minutes&nbsp;;</li>
				<li>Dans les 24h suivantes, on conserve une version toutes les 3 heures&nbsp;;</li>
				<li>Dans les 4 mois suivants, on conserve une version par mois&nbsp;;</li>
				<li>Ensuite, on conserve une seule version.</li>
			{elseif (FILE_VERSIONING_POLICY ?? $config.file_versioning_policy) === 'max'}
				<li>Dans les 10 premières minutes, on conserve une version par minute&nbsp;;</li>
				<li>Dans l'heure suivante, on conserve une version toutes les 10 minutes&nbsp;;</li>
				<li>Dans les 24h suivantes, on conserve une version par heure&nbsp;;</li>
				<li>Dans les 2 mois suivants, on conserve une version par semaine&nbsp;;</li>
				<li>Ensuite, on conserve une version par trimestre.</li>
			{/if}
			</ul>
			<p>Les <strong>versions nommées</strong> ne sont pas concernées par la suppression automatique, elles seront toujours conservées à moins d'être supprimées manuellement.</p>
		</div>
	</details>
</form>

{include file="_foot.tpl"}