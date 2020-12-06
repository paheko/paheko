{include file="admin/_head.tpl" title="Liste des rappels envoyés" current="membres/services"}

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
			<th><a href="../../membres/fiche.php?id={$row.id_user}">{$row.identity}</a></th>
			<td>{$row.email}</td>
			<td>{$row.date|date_short}</td>
			<td></td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}