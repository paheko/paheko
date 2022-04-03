{include file="admin/_head.tpl" title="Recherche de membre" current="users" custom_js=['lib/query_builder.min.js']}

{include file="users/_nav.tpl" current="search"}

{include file="common/search/advanced.tpl" action_url=$self_url}

{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
	<form method="post" action="{$admin_url}membres/action.php" class="memberList">
{/if}

{if $list !== null}
	<p class="help">{$list->count()} membres trouvés pour cette recherche.</p>

	{include file="common/dynamic_list_head.tpl" check=$is_admin use_buttons=true}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $is_admin}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>
			{/if}
			{foreach from=$row key="key" item="value"}
				<?php
				if (substr($key, 0, 1) == '_') {
					continue;
				}
				?>
				<td>
					{display_dynamic_field key=$key value=$value}
				</td>
			{/foreach}
			<td class="actions">
				{linkbutton shape="user" label="Fiche membre" href="!membres/fiche.php?id=%d"|args:$row.id}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{linkbutton shape="edit" label="Modifier" href="!membres/modifier.php?id=%d"|args:$row.id}
				{/if}
			</td>
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

{elseif $results}

	<table class="list">
		<thead>
			<tr>
				{foreach from=$header item="column"}
				<td>{$column}</td>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$results item="row"}
			<tr>
				{foreach from=$row item="column"}
				<td>{$column}</td>
				{/foreach}
			</tr>
			{foreachelse}
			<tr>
				<td colspan="{$header|count}"><p class="alert block">Aucun résultat</p></td>
			</tr>
			{/foreach}
		</tbody>
	</table>

{/if}


{include file="admin/_foot.tpl"}