{include file="admin/_head.tpl" title="Recherche" current="acc" custom_js=['lib/query_builder.min.js']}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Recherche</a></li>
		<li><a href="saved_searches.php">Recherches enregistrées</a></li>
	</ul>
</nav>

{include file="common/search/advanced.tpl" action_url=$self_url}

{*if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE)}
	<form method="post" action="{$admin_url}membres/action.php" class="memberList">
{/if*}

{if $list !== null}
	<p class="help">{$list->count()} écritures trouvées pour cette recherche.</p>

	{include file="common/dynamic_list_head.tpl" check=$is_admin}

	<?php
	$prev_id = null;
	?>

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $is_admin}<td class="check">{input type="checkbox" name="selected[]" value=$row.id}</td>{/if}
			{foreach from=$row key="key" item="value"}
				{if $prev_id == $row.id && !in_array($key, ['debit', 'credit', 'account_code', 'line_label', 'line_reference', 'project_code'])}
					<td></td>
				{elseif $key == 'id'}
				<td class="num">
					{link href="!acc/transactions/details.php?id=%d"|args:$value label="#%d"|args:$value}
				</td>
				{else}
				<td>
					{if $key == 'credit' || $key == 'debit'}
						{$value|raw|money:false}
					{elseif $key == 'date'}
						{$value|date_short}
					{else}
						{$value}
					{/if}
				</td>
				{/if}
			{/foreach}
			<td class="actions">
				{linkbutton shape="search" label="Détails" href="!acc/transactions/details.php?id=%d"|args:$row.id}
			</td>
		</tr>
		<?php $prev_id = $row->id; ?>
	{/foreach}
	</tbody>
	{*if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
		{include file="admin/membres/_list_actions.tpl" colspan=count($result_header)+1}
	{/if*}
	</table>

	{*if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
		</form>
	{/if*}

	{* FIXME pagination *}

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
	</table>

{/if}


{include file="admin/_foot.tpl"}