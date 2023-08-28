{include file="common/dynamic_list_head.tpl"}

{foreach from=$list->iterate() item="row"}
	<tr>
		<th>{if !$row.identity}*{else}{$row.identity}{/if}</th>
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
			{elseif $row.type == Log::LOGIN_AS}
				"{$row.details.admin}" s'est connecté à la place du membre
			{elseif $row.entity_url}
				{link href=$row.entity_url label=$row.entity_name}
			{elseif $row.entity_name}
				{$row.entity_name}
			{/if}
		</td>
		<td>{$row.ip_address}</td>
		<td class="actions">
		</td>
	</tr>
{/foreach}

</tbody>
</table>

{$list->getHTMLPagination()|raw}

<p class="help">Note : les heures correspondent au fuseau horaire du serveur (<?=ini_get('date.timezone')?>).</p>