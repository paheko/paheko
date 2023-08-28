<?php
use Paheko\Entities\Accounting\Account;
?>
{include file="_head.tpl" title="Comptes favoris" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

{include file="acc/accounts/_nav.tpl" current="index"}


{if isset($_GET['chart_change'])}
<p class="block error">
	L'exercice sélectionné utilise un plan comptable différent, merci de sélectionner un autre compte.
</p>
{/if}

{if $pending_count}
	{include file="acc/transactions/_pending_message.tpl"}
{/if}

{if !empty($grouped_accounts)}
	<table class="list">
		<thead>
			<tr>
				<td></td>
				<td class="num">Numéro</td>
				<th>Compte</th>
				<td class="money">Solde</td>
				<td></td>
				<td></td>
			</tr>
		</thead>
		{foreach from=$grouped_accounts item="group"}
		<tbody>
			<tr>
				<td colspan="6"><h2 class="ruler">{$group.label}</h2></td>
			</tr>
			{foreach from=$group.accounts item="account"}
				<tr class="account">
					<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
					<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.code}</a></td>
					<th><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.label}</a></th>
					<td class="money">
						{if $account.balance < 0
							|| ($account.balance > 0 && $account.position == Account::LIABILITY && ($account.type == Account::TYPE_BANK || $account.type == Account::TYPE_THIRD_PARTY || $account.type == Account::TYPE_CASH))}
							<?php $balance = abs($account->balance)*-1; ?>
							<strong class="error">{$balance|raw|money_currency:false:true}</strong>
						{else}
							{$account.balance|raw|money_currency:false}
						{/if}
					</td>
					<td>
						{if $account.type == Account::TYPE_THIRD_PARTY && $account.balance > 0}
							{if $account.position == Account::LIABILITY}<em class="alert">(Dette)</em>
							{elseif $account.position == Account::ASSET}<em class="alert">(Créance)</em>
							{/if}
						{elseif $account.type == Account::TYPE_BANK && $account.balance > 0 && $account.position == Account::LIABILITY}
							<em class="alert">(Découvert)</em>
						{elseif $account.type == Account::TYPE_CASH && $account.balance > 0 && $account.position == Account::LIABILITY}
							<em class="alert">(Anomalie)</em>
						{/if}
					</td>
					<td class="actions">
						{linkbutton label="Journal" shape="menu" href="journal.php?id=%d&year=%d"|args:$account.id,$current_year.id}
						{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
							{if $account.type == Entities\Accounting\Account::TYPE_BANK && ($account.debit || $account.credit)}
								{linkbutton label="Rapprochement" shape="check" href="reconcile.php?id=%d"|args:$account.id}
							{elseif $account.type == Entities\Accounting\Account::TYPE_OUTSTANDING && $account.debit}
								{linkbutton label="Dépôt en banque" shape="check" href="deposit.php?id=%d"|args:$account.id}
							{/if}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
		{/foreach}
	</table>
{else}
	<div class="alert block">
		<p>Aucun compte favori ne comporte d'écriture sur cet exercice.</p>
		<p>
			{linkbutton href="!acc/transactions/new.php" label="Saisir une écriture" shape="plus"}
		</p>
	</div>
{/if}

<p class="help">
	Note : n'apparaissent ici que les comptes qui ont été utilisés dans cet exercice (au moins une écriture) de types banque, caisse, tiers, dépenses ou recettes. Les autres comptes n'apparaissent que s'ils ont été utilisés et sont marqués comme favoris.<br />
	Pour voir le solde de tous les comptes, se référer à la <a href="all.php">liste de tous comptes de l'exercice</a>.<br />
	Pour voir la liste complète des comptes, même ceux qui n'ont pas été utilisés, se référer au <a href="{$admin_url}acc/charts/accounts/?id={$current_year.id_chart}">plan comptable</a>.
</p>

{include file="_foot.tpl"}