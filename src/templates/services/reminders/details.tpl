{include file="_head.tpl" title="Liste des rappels envoyés" current="users/services"}

{include file="services/_nav.tpl" current="reminders"}

<nav class="tabs">
	<ul class="sub">
		<li class="title">{$reminder.subject}</li>
		<li{if $current_list === 'pending'} class="current"{/if}>{link href="?id=%d&list=pending"|args:$reminder.id label="Rappels à envoyer"}</li>
		<li{if $current_list === 'sent'} class="current"{/if}>{link href="?id=%d&list=sent"|args:$reminder.id label="Rappels déjà envoyés"}</li>
	</ul>
</nav>

<dl class="cotisation">
	<dt>Rappel&nbsp;: <em>{$reminder.subject}</em></dt>
	<dd>Activité&nbsp;: {$service.label}</dd>
	<dd>Délai d'envoi&nbsp;: {if $reminder.delay > 0}{$reminder.delay} jours après l'expiration{elseif $reminder.delay < 0}{$reminder.delay|abs} jours avant l'expiration{else}le jour de l'expiration{/if}</dd>
	{if $current_list === 'sent'}
		<dt>Nombre de rappels envoyés</dt>
		<dd>
			{$list->count()}
		</dd>
	{elseif $current_list === 'pending'}
		<dt>Nombre de rappels à envoyer</dt>
		<dd>
			{$list->count()}
		</dd>
		{if USE_CRON && $list->count()}
			<dd class="help">Ces rappels seront envoyés dans les prochaines 24 heures.</dd>
		{/if}
	{/if}
</dl>

{if $list->count()}
	{if $current_list === 'pending'}
		<p class="help">Note : cette liste ne prend pas en compte les membres qui ont une adresse e-mail invalide, ou qui se sont désinscrit des envois de messages.</p>
	{/if}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				<th>{link href="!users/details.php?id=%d"|args:$row.id_user label=$row.identity}</th>
				{if $row.expiry_date}
					<td>{$row.expiry_date|date_short}</td>
				{else}
					<td>{$row.date|date_short}</td>
				{/if}
				<td class="actions">
				{if $current_list === 'pending'}
					{linkbutton href="preview.php?id_user=%d&id_reminder=%d"|args:$row.id_user:$reminder.id shape="eye" label="Prévisualiser" target="_dialog"}
				{/if}
				</td>
			</tr>
		{/foreach}

		</tbody>
	</table>
{elseif $current_list === 'pending'}
	<p class="block alert">Il n'y a aucun message à envoyer pour ce rappel.</p>
{else}
	<p class="block alert">Il n'y a aucun message envoyé pour ce rappel.</p>
{/if}

{$list->getHTMLPagination()|raw}


{include file="_foot.tpl"}