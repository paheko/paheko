{include file="_head.tpl" title="Journal d'audit — Historique des actions des membres" current="config"}

{include file="../_menu.tpl" current="advanced" sub_current="audit"}

<p class="help">
	Cette page liste les tentatives de connexion, les modifications de mot de passe ou d'identifiant, et toutes les actions de création, suppression ou modification effectuées par tous les membre.
</p>

{if $list->count()}
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
{else}
	<p class="block alert">
		Aucune activité trouvée.
	</p>
{/if}

</form>

{include file="_foot.tpl"}