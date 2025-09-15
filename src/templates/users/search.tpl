{include file="_head.tpl" title=$title current="users"}

{include file="users/_nav.tpl" current="search"}

<form method="post" action="{$self_url}" id="queryBuilderForm" data-disable-progress="1">

{include file="common/search/advanced.tpl"}

{if $list !== null}
	{if $list->count() > 0}
	<p class="actions">{exportmenu form=true name="_dl_export" class="menu-btn-right"}</p>
	{/if}

	<p class="help">{$list->count()} membres trouvés pour cette recherche.</p>

	{$list->getHTMLPagination(true)|raw}

	{include file="common/dynamic_list_head.tpl" check=$is_admin use_buttons=true}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $is_admin}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>
			{/if}
			{foreach from=$list->getHeaderColumns() key="name" item="label"}
				<td>
					{user_field name=$name value=$row->$name link_name_id=$row.id files_href="!users/details.php?id=%d"|args:$row.id}
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

	{$list->getHTMLPagination(true)|raw}

	</form>

{elseif $count}

	<p class="actions">{exportmenu form=true name="_export" class="menu-btn-right"}</p>

	<p class="help">
		{$count} résultats trouvés pour cette recherche.
	</p>
	{if !empty($has_limit)}
		<p class="alert block">Le nombre de résultats affichés est limité.</p>
	{/if}

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
					<td class="check"><input type="checkbox" title="Tout cocher / décocher" aria-label="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
				{/if}
				{foreach from=$header item="column"}
					<td>{$column}</td>
				{/foreach}
				{if $id_column !== false}
					<td class="actions"></td>
				{/if}
			</tr>
		</thead>
		<tbody>
			{foreach from=$results item="row"}
			<tr>
			{if $is_admin && $id_column !== false && ($value = $row[$id_column] ?? null)}
				<td class="check">{input type="checkbox" name="selected[]" value=$value}</td>
			{/if}
				{foreach from=$header key="i" item="label"}
				<td>
					<?php $value = $row[$i]; $name = $header[$i]; ?>
					{if $id_column !== false}
						{user_field name=$name value=$value files_href="!users/details.php?id=%d"|args:$row[$id_column]}
					{else}
						{$value}
					{/if}
				</td>
				{/foreach}
				{if $id_column !== false}
					<?php $id = $row[$id_column]; ?>
					<td class="actions">
						{linkbutton shape="user" href="!users/details.php?id=%d"|args:$id label="Fiche membre"}
					</td>
				{/if}
			</tr>
			{/foreach}
		</tbody>

		{if $is_admin && $id_column !== false}
			{include file="users/_list_actions.tpl" colspan=$header_count+2}
		{/if}
	</table>

	</form>

{elseif $count === 0}

	<p class="alert block">Aucun résultat trouvé pour cette recherche.</p>

{/if}

</form>

{include file="_foot.tpl"}