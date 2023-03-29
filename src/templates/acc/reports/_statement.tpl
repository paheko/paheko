<?php
$colspan = empty($year2) ? 3 : 5;
$max = max(count($statement->body_left), count($statement->body_right));
?>
<table class="statement">
	{if !empty($caption)}<caption>{$caption}</caption>{/if}
	<thead>
		<tr>
			<th colspan="{$colspan}" class="colspan">{$statement.caption_left}</th>
			<td class="spacer"></td>
			<th colspan="{$colspan}" class="colspan">{$statement.caption_right}</th>
		</tr>
	{if !empty($year2)}
		<tr>
			<td></td>
			<td></td>
			<td class="money" width="10%">{$year->label_years()}</td>
			<td class="money" width="10%">{$year2->label_years()}</td>
			<td class="money" width="10%">Écart</td>
			<td class="spacer"></td>
			<td></td>
			<td></td>
			<td class="money" width="10%">{$year->label_years()}</td>
			<td class="money" width="10%">{$year2->label_years()}</td>
			<td class="money" width="10%">Écart</td>
		</tr>
	{/if}
	</thead>
	<tbody>
		<?php for ($i = 0; $i < $max; $i++):
		$row = $statement->body_left[$i] ?? null;
		$class = $i % 2 == 0 ? 'odd' : 'even';
		?>
		<tr class="{$class}">
			{if $row}
				<td class="num">
					{if !empty($year) && $row.id}
						{link href="!acc/accounts/journal.php?id=%d&year=%d"|args:$row.id,$year.id label=$row.code}
					{else}
						{$row.code}
					{/if}
				</td>
				<th>{$row.label}</th>
				<td class="money">{$row.balance|raw|money:false}</td>
				{if isset($year2)}
					<td class="money">{$row.balance2|raw|money:false}</td>
					<td class="money">{$row.change|raw|money:false:true}</td>
				{/if}
			{else}
				<td colspan="{$colspan}" class="colspan"></td>
			{/if}
			<td class="spacer"></td>
			<?php $row = $statement->body_right[$i] ?? null; ?>
			{if $row}
				<td class="num">
					{if !empty($year) && $row.id}
						{link href="!acc/accounts/journal.php?id=%d&year=%d"|args:$row.id,$year.id label=$row.code}
					{else}
						{$row.code}
					{/if}
				</td>
				<th>{$row.label}</th>
				<td class="money">{$row.balance|raw|money:false}</td>
				{if isset($year2)}
					<td class="money">{$row.balance2|raw|money:false}</td>
					<td class="money">{$row.change|raw|money:false:true}</td>
				{/if}
			{else}
				<td colspan="{$colspan}" class="colspan"></td>
			{/if}
		</tr>
		<?php endfor; ?>
	</tbody>
	<tfoot>

		<tr class="spacer"><td colspan="{if !empty($year2)}11{else}7{/if}" class="colspan"></td></tr>
	<?php $max = max(count($statement->foot_left), count($statement->foot_right)); ?>
	<?php for ($i = 0; $i < $max; $i++):
		$row = $statement->foot_left[$i] ?? null;
		$class = $i % 2 == 0 ? 'odd' : 'even';
		?>
		<tr class="{$class}">
		{if $row}
			<th colspan="2">{$row.label}</th>
			<td class="money" width="10%">{$row.balance|raw|money:false}</td>
			{if $row.balance2 || $row.change}
			<td class="money" width="10%">{$row.balance2|raw|money:false}</td>
			<td class="money" width="10%">{$row.change|raw|money:false:true}</td>
			{/if}
		{else}
			<td colspan="{$colspan}" class="colspan"></td>
		{/if}
			<td class="spacer"></td>
		<?php $row = $statement->foot_right[$i] ?? null; ?>
		{if $row}
			<th colspan="2">{$row.label}</th>
			<td class="money" width="10%">{$row.balance|raw|money:false}</td>
			{if $row.balance2 || $row.change}
			<td class="money" width="10%">{$row.balance2|raw|money:false}</td>
			<td class="money" width="10%">{$row.change|raw|money:false:true}</td>
			{/if}
		{else}
			<td colspan="{$colspan}" class="colspan"></td>
		{/if}
		</tr>
		<?php endfor; ?>
	</tfoot>
</table>
