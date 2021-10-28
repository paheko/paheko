{include file="admin/_head.tpl" title="Restaurer" current="config"}

{include file="admin/config/_menu.tpl" current="backup"}

{include file="admin/config/backup/_menu.tpl" current="restore"}

{form_errors}

{if $code == Sauvegarde::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT}
	<p class="block alert">Pour passer outre, renvoyez le fichier en cochant la case «&nbsp;Ignorer les erreurs&nbsp;».
	Attention, si vous avez effectué des modifications dans la base de données, cela peut créer des bugs&nbsp;!</p>
{/if}

{if $ok}
	<p class="block confirm">
		{if $ok == 'restore'}La restauration a bien été effectuée.
			{if $ok_code & Sauvegarde::NOT_AN_ADMIN}
			</p>
			<p class="block alert">
				<strong>Vous n'êtes pas administrateur dans cette sauvegarde.</strong> Garradin a donné les droits d'administration à toutes les catégories afin d'empêcher de ne plus pouvoir se connecter.
				Merci de corriger les droits des catégories maintenant.
			{/if}
		{elseif $ok == 'remove'}La sauvegarde a été supprimée.
		{/if}
	</p>
{/if}


<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">

<fieldset>
	<legend><label for="f_file">Restaurer depuis un fichier de sauvegarde</label></legend>
	<p class="block alert">
		Attention, l'intégralité des données courantes seront effacées et remplacées par celles
		contenues dans le fichier fourni.
	</p>
	<p class="help">
		Une sauvegarde des données courantes sera effectuée avant le remplacement,
		en cas de besoin d'annuler cette restauration.
	</p>
	<dl>
		{input type="file" name="file" required=true}
	</dl>
	<p class="submit">
		{csrf_field key="backup_restore"}
		{button type="submit" name="restore_file" label="Restaurer depuis le fichier sélectionné" shape="upload" class="main"}
	</p>
	{if $code && ($code == Sauvegarde::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT)}
	<p>
		{input type="checkbox" name="force_import" value="1" label="Ignorer les erreurs, je sais ce que je fait"}
	</p>
	{/if}
</fieldset>

</form>

{if ENABLE_AUTOMATIC_BACKUPS}

<form method="post" action="{$self_url_no_qs}">

<fieldset>
	<legend>Sauvegardes disponibles</legend>
	{if empty($list)}
		<p class="help">Aucune copie de sauvegarde disponible.</p>
	{else}
		<table class="list">
			<tbody>
				<thead>
					<tr>
						<td></td>
						<th>Nom</th>
						<td>Taille</td>
						<td>Date</td>
						<td>Version</td>
						<td></td>
					</tr>
				</thead>
			{foreach from=$list item="backup"}
				<tr>
					<td class="check">{input type="radio" name="selected" value=$backup.filename}</td>
					<th><label for="f_selected_{$backup.filename}">{$backup.name}</label></th>
					<td>{$backup.size|size_in_bytes}</td>
					<td>{$backup.date|date_short:true}</td>
					<td>{$backup.version}{if !$backup.can_restore} — <span class="alert">Version trop ancienne pour pouvoir être restaurée</span>{/if}</td>
					<td class="actions">
						{linkbutton href="?download=%s"|args:$backup.filename label="Télécharger" shape="download"}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		<p class="alert block">
			Attention, en cas de restauration, l'intégralité des données courantes seront effacées et remplacées par celles contenues dans la sauvegarde sélectionnée.
		</p>
		<p class="submit">
			{csrf_field key="backup_manage"}
			{button type="submit" name="restore" label="Restaurer cette sauvegarde" shape="reset" class="main"}
			{button type="submit" name="remove" label="Supprimer cette sauvegarde" shape="delete"}
		</p>
	{/if}
</fieldset>

</form>

{/if}

{include file="admin/_foot.tpl"}