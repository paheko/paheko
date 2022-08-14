{include file="_head.tpl" title="Rappels envoyés à un membre" current="users/services"}

<nav class="tabs">
	<ul>
		<li>{link href="!users/details.php?id=%d"|args:$user_id label="Fiche membre"}</li>
		<li>{link href="!services/user/?id=%d"|args:$user_id label="Inscriptions aux activités"}</li>
		<li class="current">{link href="!services/reminders/user.php?id=%d"|args:$user_id label="Rappels envoyés"}</li>
	</ul>
</nav>

{if $list->count()}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				<th>{$row.label}</th>
				<td>{if $row.delay > 0}{$row.delay} jours après l'expiration{elseif $row.delay < 0}{$row.delay|abs} jours avant l'expiration{else}le jour de l'expiration{/if}</td>
				<td>{$row.date|date_short}</td>
				<td></td>
			</tr>
		{/foreach}

		</tbody>
	</table>

	{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{else}

	<p class="alert block">Aucun rappel n'a été envoyé à ce membre.</p>

{/if}

{include file="_foot.tpl"}