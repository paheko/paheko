{include file="_head.tpl" title="Rappels envoyés à un membre" current="users/services"}

{include file="users/_nav_user.tpl" id=$user_id current="reminders"}

{if $list->count()}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				<th>{$row.label}</th>
				<td>{if $row.delay > 0}{$row.delay} jours après l'expiration{elseif $row.delay < 0}{$row.delay|abs} jours avant l'expiration{else}le jour de l'expiration{/if}</td>
				<td>{$row.date|date_short}</td>
				<td>
					{linkbutton shape="menu" label="Inscriptions après ce rappel" href="!services/user/?id=%d&after=%s"|args:$user_id,$row.date}
				</td>
			</tr>
		{/foreach}

		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}


{else}

	<p class="alert block">Aucun rappel n'a été envoyé à ce membre.</p>

{/if}

{include file="_foot.tpl"}