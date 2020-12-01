{include file="admin/_head.tpl" title="Gestion des rappels automatiques" current="membres/services"}

{include file="services/_nav.tpl" current="reminders"}

<p class="help">
	Les rappels automatiques sont envoyés aux membres disposant d'une adresse e-mail selon le délai défini. Il est possible de définir plusieurs rappels pour une même activité.
</p>

{if empty($list)}
	<p class="alert block">Aucun rappel automatique n'est configuré.</p>
{else}
	<table class="list">
		<thead>
			<td>Activité</td>
			<td>Délai de rappel</td>
			<th>Sujet</th>
			<td></td>
		</thead>
		<tbody>
			{foreach from=$list item="reminder"}
				<tr>
					<td>
						{$reminder.service_label}
					</td>
					<td>
						{if $reminder.delay == 0}le jour de l'expiration
						{else}
							{$reminder.delay|abs}
							{if abs($reminder.delay) > 1}jours{else}jour{/if}
							{if $reminder.delay > 0}après{else}avant{/if}
							expiration
						{/if}
					</td>
					<th><a href="details.php?id={$reminder.id}">{$reminder.subject}</a></th>
					<td class="actions">
						{linkbutton shape="mail" label="Liste des rappels envoyés" href="!services/reminders/details.php?id=%d"|args:$reminder.id}
						{linkbutton shape="edit" label="Modifier" href="!services/reminders/edit.php?id=%d"|args:$reminder.id}
						{linkbutton shape="delete" label="Supprimer" href="!services/reminders/delete.php?id=%d"|args:$reminder.id}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

{include file="services/reminders/_form.tpl" legend="Ajouter un rappel automatique"
	reminder=null delay_type=0 delay_before=15 delay_after=5}

{include file="admin/_foot.tpl"}