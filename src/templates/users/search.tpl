{include file="admin/_head.tpl" title="Recherche de membre" current="membres" custom_js=['lib/query_builder.min.js']}

{include file="users/_nav.tpl" current="search"}

{include file="common/search/advanced.tpl" action_url=$self_url}

{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
	<form method="post" action="{$admin_url}membres/action.php" class="memberList">
{/if}

{if $list !== null}
	<p class="help">{$list->count()} membres trouvés pour cette recherche.</p>

	{include file="common/dynamic_list_head.tpl" check=$is_admin}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $is_admin}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>
			{/if}
			{foreach from=$columns key="key" item="column"}
				<?php
				if (substr($key, 0, 1) == '_') {
					continue;
				}
				?>
				<td>
					{display_dynamic_field field=$key value=$row[$key]}
				</td>
				<td class="actions">
					{linkbutton shape="user" label="Fiche membre" href="!membres/fiche.php?id=%d"|args:$row.id}
					{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
						{linkbutton shape="edit" label="Modifier" href="!membres/modifier.php?id=%d"|args:$row.id}
					{/if}
				</td>
			{/foreach}
		</tr>
	{/foreach}
		</tbody>
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && $row._user_id}
		{include file="admin/membres/_list_actions.tpl" colspan=count($result_header)+1}
	{/if}
	</table>

	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
		</form>
	{/if}

{else}
ss
{/if}


{include file="admin/_foot.tpl"}