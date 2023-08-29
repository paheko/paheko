{include file="_head.tpl" title="Restaurer" current="config"}

{include file="config/_menu.tpl" current="backup"}

{include file="config/backup/_menu.tpl" current="restore"}

{form_errors}

{if $code == Backup::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT}
	<p class="block alert">Pour passer outre, renvoyez le fichier en cochant la case «&nbsp;Ignorer les erreurs&nbsp;».
	Attention, si vous avez effectué des modifications dans la base de données, cela peut créer des bugs&nbsp;!</p>
{/if}

{if $ok}
	<p class="block confirm">
		{if $ok == 'restore'}La restauration a bien été effectuée.
			{if $ok_code & Backup::NOT_AN_ADMIN}
			</p>
			<p class="block alert">
				<strong>Vous n'êtes pas administrateur dans cette sauvegarde.</strong> Paheko a donné les droits d'administration à toutes les catégories afin d'empêcher de ne plus pouvoir se connecter.
				Merci de corriger les droits des catégories maintenant.
			{elseif $ok_code & Backup::CHANGED_USER}
			</p>
			<p class="block alert">
				<strong>Votre compte membre n'existait pas dans la sauvegarde qui a été restaurée, vous êtes désormais connecté avec le premier compte administrateur.</strong>
			</p>
			{/if}
		{elseif $ok == 'remove'}La sauvegarde a été supprimée.
		{/if}
	</p>
{/if}

{if $_GET.from_file}
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
			{input type="file" name="file" label="Fichier de sauvegarde à restaurer" required=true}
		</dl>
		<p class="submit">
			{csrf_field key="backup_restore"}
			{button type="submit" name="restore_file" label="Restaurer depuis le fichier sélectionné" shape="upload" class="main"}
		</p>
		{if $code && ($code == Backup::INTEGRITY_FAIL && ALLOW_MODIFIED_IMPORT)}
		<p>
			{input type="checkbox" name="force_import" value="1" label="Ignorer les erreurs, je sais ce que je fait"}
		</p>
		{/if}
	</fieldset>

	</form>

{else}

	{if !$code && !$ok}
	<p class="help">
		Espace disque occupé par les sauvegardes : <strong>{$size|size_in_bytes}</strong>
	</p>
	{/if}

	<form method="post" action="{$self_url_no_qs}">

	{if empty($list)}
		<p class="alert block">Aucune copie de sauvegarde disponible.</p>
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
					<td class="check">{if $backup.can_restore}{input type="radio" name="selected" value=$backup.filename}{/if}</td>
					<th><label for="f_selected_{$backup.filename}">{$backup.name}</label></th>
					<td>{$backup.size|size_in_bytes}</td>
					<td>{$backup.date|date_short:true}</td>
					<td>{if $backup.error}
							<span class="alert">Sauvegarde corrompue :</span> {$backup.error}
						{else}
							{$backup.version}{if !$backup.can_restore} — <span class="alert">Version trop ancienne pour pouvoir être restaurée</span>{/if}
						{/if}
					</td>
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
			{button type="submit" name="restore" label="Restaurer la sauvegarde sélectionnée" shape="reset" class="main"}
			{button type="submit" name="remove" label="Supprimer la sauvegarde sélectionnée" shape="delete"}
		</p>
	{/if}

	</form>
{/if}

{include file="_foot.tpl"}