{include file="_head.tpl" title="Journal de connexion"}

{if $id && $current == 'users'}
	{include file="users/_nav_user.tpl" id=$id}
{/if}

{if $list->count()}
	{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if !$id}
			<th>{if !$row.identity}*{else}{$row.identity}{/if}</th>
			{/if}
			<td>{$row.created|date_short:true}</td>
			<td class="help">
				{if $row.type == Log::LOGIN_FAIL || $row.type == Log::LOGIN_PASSWORD_CHANGE}
					<span class="alert">{icon shape="alert"}</span>
				{/if}
			</td>
			<td>
				{$row.type_label}
			</td>
			<td>
				{if $row.type == Log::LOGIN_FAIL && $row.details.otp}
				<strong>Code OTP erroné</strong><br />
				{elseif $row.type == Log::LOGIN_SUCCESS && $row.details.otp}
				<strong>(avec code OTP)</strong><br />
				{/if}
				{if $row.type == Log::LOGIN_FAIL || $row.type == Log::LOGIN_SUCCESS || $row.type == Log::LOGIN_RECOVER}
					{$row.details.user_agent}
				{/if}
			</td>
			<td>{$row.ip_address}</td>
			<td class="actions">
			</td>
		</tr>
	{/foreach}

	</tbody>
	</table>

	{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}
{else}
	<p class="block alert">
		Aucune activité trouvée.
	</p>
{/if}

</form>

{include file="_foot.tpl"}