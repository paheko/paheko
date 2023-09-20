{include file="_head.tpl" title="Recherche" current="acc" custom_js=['lib/query_builder.min.js']}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Recherche</a></li>
		<li><a href="saved_searches.php">Recherches enregistrées</a></li>
	</ul>
</nav>

<form method="post" action="{$self_url_no_qs}" id="queryBuilderForm" data-disable-progress="1">

{include file="common/search/advanced.tpl"}

{if $list !== null}
	<p class="help">{$list->count()} écritures trouvées pour cette recherche.</p>

	{if $list->count() > 0}
	<p class="actions">{exportmenu form=true name="_dl_export" class="menu-btn-right"}</p>
	{/if}


	{include file="common/dynamic_list_head.tpl" check=$is_admin use_buttons=true}

	<?php
	$prev_id = null;
	$debit = null;
	$credit = null;
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
				{elseif $key == 'credit' || $key == 'debit'}
					<td class="money">
						<?php
						${$key} += $value;
						?>
						{$value|raw|money:false}
					</td>
				{else}
				<td>
					{if $key == 'date'}
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

	{if $debit !== null || $credit !== null}
	<?php
	$span1 = 0;
	foreach ($row as $key => $v) {
		if ($key == 'credit' || $key == 'debit') {
			break;
		}
		$span1++;
	}
	$span2 = count((array)$row) - $span1;
	?>
	<tfoot>
		<tr>
			{if $is_admin}<td></td>{/if}
			<td colspan="{$span1}"><strong>Totaux de cette page</strong></td>
			{foreach from=$row key="key" item="value"}
				{if $key == 'credit' || $key == 'debit'}
				<td class="money">
					<?php $total = ${$key}; ?>
					{$total|raw|money:false}
				</td>
				{/if}
			{/foreach}
			<td colspan="{$span2}"></td>
		</tr>
	</tfoot>
	{/if}

	</table>

	{$list->getHTMLPagination(true)|raw}

{elseif $count}

	<p class="actions">{exportmenu form=true name="_export" class="menu-btn-right"}</p>

	<p class="help">{$count} résultats trouvés pour cette recherche.</p>

	<?php
	$id_column = array_search('id', $header, true);
	?>

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
				{foreach from=$row key="key" item="value"}
					{if $id_column === $key}
						<td class="num">{link href="!acc/transactions/details.php?id=%d"|args:$value label="#%d"|args:$value}</td>
					{else}
						<td>{$value}</td>
					{/if}
				{/foreach}
			</tr>
			{/foreach}
		</tbody>
	</table>

{elseif $count === 0}

	<p class="alert block">Aucun résultat trouvé pour cette recherche.</p>

{/if}

</form>

{include file="_foot.tpl"}