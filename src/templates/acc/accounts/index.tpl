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
			<tr class="no-border">
				<td colspan="2"><span class="ruler-left"></span></td>
				<td colspan="4"><h2 class="ruler-left">{$group.label}</h2></td>
			</tr>
			{foreach from=$group.accounts item="account"}
				<tr class="account">
					<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
					<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.code}</a></td>
					<th><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.label}</a></th>
					<td class="money">
						{show_balance account=$account}
					</td>
					<td>
						{if $account.type == Account::TYPE_THIRD_PARTY && $account.balance > 0}
							{if $account.position == Account::LIABILITY}{tag preset="debt"}</em>
							{elseif $account.position == Account::ASSET}{tag preset="credit"}</em>
							{/if}
						{elseif $account.type == Account::TYPE_BANK && $account.balance > 0 && $account.position == Account::LIABILITY}
							{tag preset="overdraft"}
						{elseif $account.type == Account::TYPE_CASH && $account.balance > 0 && $account.position == Account::LIABILITY}
							{tag preset="anomaly"}
						{/if}
						{if $account.type === Account::TYPE_BANK && $account.reconciled_balance}
							{if $account.reconciled_balance != $account.balance}
								{tag small=true preset="reconciliation_required"}
							{else}
								{tag small=true preset="reconciled"}
							{/if}
						{/if}
					</td>
					<td class="actions">
						{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
							{if $account.type === Entities\Accounting\Account::TYPE_BANK && ($account.debit || $account.credit)}
								{linkbutton label="Rapprochement" shape="check" href="reconcile.php?id=%d"|args:$account.id}
							{elseif $account.type === Entities\Accounting\Account::TYPE_OUTSTANDING && $account.debit}
								{linkbutton label="Dépôt en banque" shape="check" href="deposit.php?id=%d&from_year=%d"|args:$account.id:$current_year.id}
							{/if}
						{/if}
						{linkbutton label="Journal" shape="menu" href="journal.php?id=%d&year=%d"|args:$account.id,$current_year.id}
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
	Note : n'apparaissent ici que les comptes favoris qui ont été utilisés dans cet exercice (au moins une écriture).<br />
	Pour voir le solde des comptes qui ne sont pas marqués comme favoris, se référer à la <a href="all.php">liste de tous les comptes de l'exercice</a>.<br />
	Pour voir la liste complète des comptes, même ceux qui n'ont pas été utilisés, se référer au <a href="{$admin_url}acc/charts/accounts/?id={$current_year.id_chart}">plan comptable</a>.
</p>

{include file="_foot.tpl"}