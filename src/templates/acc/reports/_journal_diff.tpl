<table class="list multi">
	<thead>
		<tr>
			<td class="num">N°</td>
			<td>Pièce comptable</td>
			<td>Date</td>
			<th>Libellé</th>
			{if !empty($with_linked_users)}<td>Membres associés</td>{/if}
			<td>Comptes</td>
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			<td>Libellé ligne</td>
			<td>Réf. ligne</td>
			<td>Projet</td>
		</tr>
	</thead>
	{foreach from=$journal item="t"}
	<?php
	$transaction = $t['transaction'];
	$diff = $t['diff'];
	$lines_count = isset($diff['transaction']) ? count($diff['lines']) + count($diff['lines_removed']) + count($diff['lines_new']) :  count($transaction->getLines());
	?>
	<tbody>
		<tr>
			<td rowspan="{$lines_count}" class="num">{if $transaction.id}<a href="{$admin_url}acc/transactions/details.php?id={$transaction.id}">#{$transaction.id}</a>{/if}</td>
			<td rowspan="{$lines_count}">
				{if $diff.transaction.reference}
					<del>{$diff.transaction.reference[0]}</del><br />
					<ins>{$diff.transaction.reference[1]}</ins>
				{else}
					{$transaction.reference}
				{/if}
			</td>
			<td rowspan="{$lines_count}">
				{if $diff.transaction.date}
					<del>{$diff.transaction.date[0]|date_short}</del><br />
					<ins>{$diff.transaction.date[1]|date_short}</ins>
				{else}
					{$transaction.date|date_short}
				{/if}
			</td>
			<th rowspan="{$lines_count}">
				{if $diff.transaction.label}
					<del>{$diff.transaction.label[0]}</del><br />
					<ins>{$diff.transaction.label[1]}</ins>
				{else}
					{$transaction.label}
				{/if}
			</th>
			{if !empty($with_linked_users)}
			<td rowspan="{$lines_count}">
				{if $diff.linked_users}
					<del>{$diff.linked_users[0]}</del><br />
					<ins>{$diff.linked_users[1]}</ins>
				{else}
					{$t.linked_users}
				{/if}
			</td>
			{/if}
	{if $diff.lines_removed || $diff.lines_new || $diff.lines}
		{foreach from=$diff.lines_removed item="line"}
			<td><del>{$line.account}</del></td>
			<td class="money"><del>{$line.debit|raw|money}</del></td>
			<td class="money"><del>{$line.credit|raw|money}</del></td>
			<td><del>{$line.label}</del></td>
			<td><del>{$line.reference}</del></td>
			<td><del>{$line.project}</del></td>
		</tr>
		<tr>
		{/foreach}
		{foreach from=$diff.lines_new item="line"}
			<td><ins>{$line.account}</ins></td>
			<td class="money"><ins>{$line.debit|raw|money}</ins></td>
			<td class="money"><ins>{$line.credit|raw|money}</ins></td>
			<td><ins>{$line.label}</ins></td>
			<td><ins>{$line.reference}</ins></td>
			<td><ins>{$line.project}</ins></td>
		</tr>
		<tr>
		{/foreach}
		{foreach from=$diff.lines item="line"}
			<td>
				{if $line.diff.account}
					<del>{$line.diff.account[0]}</del>
					<ins>{$line.diff.account[1]}</ins>
				{else}
					{$line.account}
				{/if}
			</td>
			<td class="money">
				{if $line.diff.debit}
					<del>{$line.diff.debit[0]|raw|money:false}</del>
					<ins>{$line.diff.debit[1]|raw|money:false}</ins>
				{else}
					{$line.debit|raw|money}
				{/if}
			</td>
			<td class="money">
				{if $line.diff.credit}
					<del>{$line.diff.credit[0]|raw|money:false}</del>
					<ins>{$line.diff.credit[1]|raw|money:false}</ins>
				{else}
					{$line.credit|raw|money}
				{/if}
			</td>
			<td>
				{if $line.diff.label}
					<del>{$line.diff.label[0]}</del>
					<ins>{$line.diff.label[1]}</ins>
				{else}
					{$line.label}
				{/if}
			</td>
			<td>
				{if $line.diff.reference}
					<del>{$line.diff.reference[0]}</del>
					<ins>{$line.diff.reference[1]}</ins>
				{else}
					{$line.reference}
				{/if}
			</td>
			<td>
				{if $line.diff.project}
					<del>{$line.diff.project[0]}</del>
					<ins>{$line.diff.project[1]}</ins>
				{else}
					{$line.project}
				{/if}
			</td>
		</tr>
		<tr>
		{/foreach}
	{else}
		{foreach from=$transaction->getLinesWithAccounts() item="line"}
			<td>{$line.account_code} - {$line.account_label}</td>
			<td class="money">{$line.debit|raw|money}</td>
			<td class="money">{$line.credit|raw|money}</td>
			<td>{$line.label}</td>
			<td>{$line.reference}</td>
			<td>{$line.project}</td>
		</tr>
		<tr>
		{/foreach}
	{/if}
		</tr>
	</tbody>
	{/foreach}
</table>