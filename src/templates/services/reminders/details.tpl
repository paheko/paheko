{include file="_head.tpl" title="Liste des rappels envoyés" current="users/services"}

{include file="services/_nav.tpl" current="reminders"}

<dl class="cotisation">
	<dt>Rappel&nbsp;: <em>{$reminder.subject}</em></dt>
	<dd>Activité&nbsp;: {$service.label}</dd>
	<dd>Délai d'envoi&nbsp;: {if $reminder.delay > 0}{$reminder.delay} jours après l'expiration{elseif $reminder.delay < 0}{$reminder.delay|abs} jours avant l'expiration{else}le jour de l'expiration{/if}</dd>
	<dt>Nombre de rappels envoyés</dt>
	<dd>
		{$list->count()}
	</dd>
</dl>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{link href="!users/details.php?id=%d"|args:$row.id_user label=$row.identity}</th>
			<td>{$row.email}</td>
			<td>{$row.date|date_short}</td>
			<td></td>
		</tr>
	{/foreach}

	</tbody>
</table>

{$list->getHTMLPagination()|raw}


{include file="_foot.tpl"}