{include file="_head.tpl" title="Recherche de membre" current="users" custom_js=['lib/query_builder.min.js']}

{include file="users/_nav.tpl" current="search"}

<form method="post" action="{$self_url_no_qs}" id="queryBuilderForm" data-disable-progress="1">

{include file="common/search/advanced.tpl"}

{if $list !== null}
	{$list->getHTMLPagination(true)|raw}

	{if $list->count() > 0}
	<p class="actions">{exportmenu form=true name="_dl_export" class="menu-btn-right"}</p>
	{/if}

	<p class="help">{$list->count()} membres trouvés pour cette recherche.</p>

	</form>
	<form method="post" action="action.php" target="_dialog">

	{include file="common/dynamic_list_head.tpl" check=$is_admin use_buttons=true}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $is_admin}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="key" item="label"}
				<td>
					{display_dynamic_field key=$key value=$row->$key}
				</td>
			{/foreach}
			<td class="actions">
				{linkbutton shape="user" label="Fiche membre" href="!users/details.php?id=%d"|args:$row.id}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{linkbutton shape="edit" label="Modifier" href="!users/edit.php?id=%d"|args:$row.id}
				{/if}
			</td>
		</tr>
	{/foreach}
		</tbody>
	{if $is_admin}
		{include file="users/_list_actions.tpl" colspan=$list->countHeaderColumns()+1}
	{/if}
	</table>

	</form>

{elseif $results}

	<p class="actions">{exportmenu form=true name="_export" class="menu-btn-right"}</p>

	<?php
	$id_column = array_search('_user_id', $header, true);

	if (false === $id_column) {
		$id_column = array_search('id', $header, true);
	}

	$header_count = count($header);
	?>

	<form method="post" action="action.php" target="_dialog">

	<table class="list">
		<thead>
			<tr>
			{if $is_admin && $id_column !== false}
				<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
			{/if}
				{foreach from=$header item="column"}
				<td>{$column}</td>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$results item="row"}
			<tr>
			{if $is_admin && $id_column !== false}
				<td class="check">{input type="checkbox" name="selected[]" value=$row[$id_column]}</td>
			{/if}
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

		{if $is_admin && $id_column !== false}
			{include file="users/_list_actions.tpl" colspan=$header_count+1}
		{/if}
	</table>

	</form>

{/if}

</form>

{include file="_foot.tpl"}