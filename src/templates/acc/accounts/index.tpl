<?php
use Garradin\Entities\Accounting\Account;
?>
{include file="admin/_head.tpl" title="Comptes favoris" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

{include file="acc/accounts/_nav.tpl" current="index"}


{if isset($_GET['chart_change'])}
<p class="block error">
	L'exercice sélectionné utilise un plan comptable différent, merci de sélectionner un autre compte.
</p>
{/if}

{include file="acc/_simple_help.tpl" link="../reports/trial_balance.php?year=%d"|args:$current_year.id type=null}

{if !empty($grouped_accounts)}
	<?php $has_accounts = false; ?>
	<table class="list">
		<thead>
			<tr>
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
				<td colspan="5"><h2 class="ruler">{$group.label}</h2></td>
			</tr>
			{foreach from=$group.accounts item="account"}
				<tr>
					<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.code}</a></td>
					<th><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.label}</a></th>
					<td class="money">
						{if $account.balance < 0
							|| ($account.balance > 0 && $account.position == Account::LIABILITY && ($account.type == Account::TYPE_BANK || $account.type == Account::TYPE_THIRD_PARTY || $account.type == Account::TYPE_CASH))}
							<strong class="error">-{$account.balance|raw|abs|money_currency:false}</strong>
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
		<?php $has_accounts = true; ?>
		{/foreach}
	</table>

	{if !$has_accounts}
	<div class="alert block">
		<p>Aucun compte favori ne comporte d'écriture sur cet exercice.</p>
		<p>
			{linkbutton href="!acc/transactions/new.php" label="Saisir une écriture" shape="plus"}
		</p>
	</div>
	{/if}
{/if}

<p class="help">
	Note : n'apparaissent ici que les comptes <strong>favoris</strong> qui ont été utilisés dans cet exercice (au moins une écriture).<br />
	Pour voir le solde de tous les comptes, se référer à la <a href="all.php">liste de tous comptes de l'exercice</a>.<br />
	Pour voir la liste complète des comptes, même ceux qui n'ont pas été utilisés, se référer au <a href="{$admin_url}acc/charts/accounts/?id={$current_year.id_chart}">plan comptable</a>.
</p>

{include file="admin/_foot.tpl"}