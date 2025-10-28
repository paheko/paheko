{include file="_head.tpl" title="File d'envoi" current="users/mailing"}

{include file="./_nav.tpl" current="queue"}

{if $_GET.msg === 'EMPTY'}
<p class="confirm block">
	Les messages en attente ont été envoyés.
</p>
{/if}

<p class="help">Cette page affiche les e-mails qui sont en attente d'être envoyés.</p>

{if !$count}
	<p class="alert block">Il n'y a aucun message en attente d'envoi.</p>
{else}
	<p class="help">
		{if USE_CRON}
			Il y a {$count} messages dans la file d'attente, ils seront envoyés dans quelques minutes par une tâche automatique.
		{else}
			Il y a {$count} messages dans la file d'attente, cliquez ici pour envoyer les messages :
			{linkbutton shape="right" label="Envoyer les messages en attente" href="?run=1"}
		{/if}
	</p>

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
		<tr>
			<td>{$contexts[$row.context]}</td>
			<td>{tag color=$statuses_colors[$row.status] label=$statuses[$row.status]}</td>
			<td>{$row.sender}</td>
			<td>{$row.recipient}</td>
			<td>{$row.subject}</td>
			<td class="actions">
				{linkbutton href="?id=%d"|args:$row.id label="Ouvrir" target="_dialog" shape="eye"}
			</td>
		</tr>

		{/foreach}
	</tbody>
	</table>

	{$list->getHTMLPagination()|raw}


{/if}

{include file="_foot.tpl"}