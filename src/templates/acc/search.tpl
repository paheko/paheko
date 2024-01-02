{include file="_head.tpl" title="Recherche" current="acc"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$self_url}">Recherche</a></li>
		<li><a href="saved_searches.php">Recherches enregistrées</a></li>
	</ul>
</nav>

<form method="post" action="{$self_url_no_qs}" id="queryBuilderForm" data-disable-progress="1">

{include file="common/search/advanced.tpl" legend="Rechercher des écritures…"}

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
			{if $is_admin && $row.id_line && $row.id}
				<td class="check">{input type="checkbox" name="check[%s]"|args:$row.id_line value=$row.id}</td>
			{/if}
			{foreach from=$row key="key" item="value"}
				<?php
				$column = $columns[$key] ?? null;

				if (!isset($column['label'])) {
					continue;
				}
				?>
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
					{if $key === 'date'}
						{$value|date_short}
					{elseif $column.type === 'boolean'}
						{if $value === null}
							—
						{elseif $value}
							Oui
						{else}
							Non
						{/if}
					{else}
						{$value}
					{/if}
				</td>
				{/if}
			{/foreach}
			<td class="actions">
				{if $prev_id != $row.id}
					{linkbutton shape="search" label="Détails" href="!acc/transactions/details.php?id=%d"|args:$row.id}
				{/if}
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
	if ($is_admin) {
		$span1--;
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
			<td colspan="{$span2}" class="actions">
				{if $is_admin}
					{include file="acc/_table_actions.tpl"}
				{/if}
			</td>
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
	$id_line_column = array_search('id_line', $header, true);
	$colspan = count($header) + 1;
	$prev_id = null;
	?>

	<table class="list">
		<thead>
			<tr>
				{if $is_admin}
					<td class="check"></td>
				{/if}
				{foreach from=$header item="column"}
				<td{if $column === 'id'} class="num"{/if}>{$column}</td>
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach from=$results item="row"}
			<tr>
				<?php $id = $row[$id_column] ?? null; ?>
				<?php $id_line = $row[$id_line_column] ?? null; ?>
				{if $is_admin && $id_column !== false && $id_line_column !== false}
					<td class="check">{input type="checkbox" name="check[%s]"|args:$id_line value=$id}</td>
				{elseif $is_admin}
					<td class="check"></td>
				{/if}
				{foreach from=$row key="key" item="value"}
					{if $prev_id == $id && $key === $id_column}
						<td></td>
					{elseif $id_column === $key}
						<td class="num">{link href="!acc/transactions/details.php?id=%d"|args:$value label="#%d"|args:$value}</td>
					{else}
						<td>{$value}</td>
					{/if}
				{/foreach}
			</tr>
			<?php $prev_id = $id; ?>
			{/foreach}
		</tbody>
		{if $is_admin && $id_column !== false && $id_line_column !== false}
			<tfoot>
				<tr>
					<td colspan="{$colspan}" class="actions">
						{include file="acc/_table_actions.tpl"}
					</td>
				</tr>
			</tfoot>
		{/if}
	</table>

{elseif $count === 0}

	<p class="alert block">Aucun résultat trouvé pour cette recherche.</p>

{/if}

</form>

{include file="_foot.tpl"}