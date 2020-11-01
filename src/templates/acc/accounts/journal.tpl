{include file="admin/_head.tpl" title="Journal : %s - %s"|args:$account.code:$account.label current="acc/accounts" body_id="rapport"}

{if empty($year)}
	{include file="acc/_year_select.tpl"}
{else}
	<nav class="acc-year">
		<h4>Exercice sélectionné&nbsp;:</h4>
		<h3>{$year.label} — {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}</h3>
	</nav>
{/if}

{if $account.type}

	{if $simple && $account::isReversed($account.type)}
		{include file="acc/_simple_help.tpl" link="?id=%d&simple=0&year=%d"|args:$account.id,$year.id type=$account.type}
	{/if}

	{if $simple}
		{if $account.type == $account::TYPE_THIRD_PARTY}
			{if $sum < 0}
				<p class="alert block">Vous devez <strong>{$sum|abs|raw|html_money}&nbsp;{$config.monnaie}</strong> à ce tiers.</p>
			{elseif $sum > 0}
				<p class="alert block">Ce tiers vous doit <strong>{$sum|raw|html_money}&nbsp;{$config.monnaie}</strong>.</p>
			{else}
				<p class="confirm block">Vous ne devez pas d'argent à ce tiers, et il ne vous en doit pas non plus.</p>
			{/if}
		{elseif $account.type == $account::TYPE_BANK}
			{if $sum < 0}
				<p class="error block">Ce compte est à découvert de <strong>{$sum|abs|raw|html_money}&nbsp;{$config.monnaie}</strong> à la banque.</p>
			{elseif $sum >= 0}
				<p class="confirm block">Ce compte est créditeur de <strong>{$sum|raw|html_money}&nbsp;{$config.monnaie}</strong> à la banque.</p>
			{/if}
		{elseif $account.type == $account::TYPE_CASH}
			{if $sum < 0}
				<p class="error block">Cette caisse est débiteur de <strong>{$sum|abs|raw|html_money}&nbsp;{$config.monnaie}</strong>. Est-ce normal&nbsp;? Une vérification est peut-être nécessaire&nbsp;?</p>
			{elseif $sum >= 0}
				<p class="confirm block">Cette caisse est créditrice de <strong>{$sum|raw|html_money}&nbsp;{$config.monnaie}</strong>.</p>
			{/if}
		{elseif $account.type == $account::TYPE_OUTSTANDING}
			{if $sum < 0}
				<p class="error block">Ce compte est débiteur <strong>{$sum|abs|raw|html_money}&nbsp;{$config.monnaie}</strong>. Est-ce normal&nbsp;? Une vérification est peut-être nécessaire&nbsp;?</p>
			{elseif $sum >= 0}
				<p class="confirm block">Ce compte d'attente est créditeur de <strong>{$sum|raw|html_money}&nbsp;{$config.monnaie}</strong>. {if $sum > 200}Un dépôt à la banque serait peut-être une bonne idée&nbsp;?{/if}</p>
			{/if}
		{/if}
	{/if}


	<nav class="tabs">
		<ul>
			<li{if $simple} class="current"{/if}><a href="?id={$account.id}&amp;simple=1&amp;year={$year.id}">Vue simplifiée</a></li>
			<li{if !$simple} class="current"{/if}><a href="?id={$account.id}&amp;simple=0&amp;year={$year.id}">Vue comptable</a></li>
		{if $year.id == CURRENT_YEAR_ID}
			<li><form method="get" action="../transactions/new.php">{button label="Saisir une écriture dans ce compte" shape="plus" type="submit"}<input type="hidden" name="account" value="{$account.id}" /></form></li>
		{/if}
		</ul>
{/if}

<table class="list">
	<colgroup>
		<col width="3%" />
		<col width="12%" />
		<col width="10%" />
		{if !$simple}<col width="10%" />{/if}
		<col width="12%" />
		<col />
		<col width="6%" />
	</colgroup>
	<thead>
		<tr>
			<td>Réf.</td>
			<td>Date</td>
			{if $simple}
			<td>Mouvement</td>
			{else}
			<td class="money">Débit</td>
			<td class="money">Crédit</td>
			{/if}
			<td class="money">Solde cumulé</td>
			<th>Libellé</th>
			<td></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$journal item="line"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">{if $line.line_reference}{$line.line_reference}{elseif $line.reference}{$line.reference}{else}#{$line.id}{/if}</a></td>
			<td>{$line.date|date_fr:'d/m/Y'}</td>
			{if $simple}
			<td class="money">{if $line.change > 0}+{else}-{/if}{$line.change|abs|raw|html_money}</td>
			{else}
			<td class="money">{$line.debit|raw|html_money}</td>
			<td class="money">{$line.credit|raw|html_money}</td>
			{/if}
			<td class="money">{$line.running_sum|raw|html_money:false}</td>
			<th>{$line.label}</th>
			<td class="actions">
				{linkbutton href="acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
			</td>
		</tr>
	{/foreach}
	</tbody>
	<tfoot>
		<tr>
			<td colspan="{if $simple}3{else}4{/if}">Solde</td>
			<td class="money">{$sum|raw|html_money:false}</td>
			<td colspan="2"></td>
		</tr>
	</tfoot>
</table>

{include file="admin/_foot.tpl"}