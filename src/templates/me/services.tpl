<?php
use Paheko\Entities\Accounting\Account;
?>
{include file="_head.tpl" title="Mes activités & cotisations" current="me/services"}

<dl class="cotisation">
	<dt>Mes activités et cotisations</dt>
	{foreach from=$services item="service"}
	<dd{if $service.archived} class="disabled"{/if}>
		{$service.label}
		{if $service.archived} <em>(activité passée)</em>{/if}
		{if $service.status == -1 && $service.end_date} — expirée
		{elseif $service.status == -1} — <b class="error">en retard</b>
		{elseif $service.status == 1 && $service.end_date} — <b class="confirm">en cours</b>
		{elseif $service.status == 1} — <b class="confirm">à jour</b>{/if}
		{if $service.status.expiry_date} — expire le {$service.expiry_date|date_short}{/if}
		{if !$service.paid} — <b class="error">À payer&nbsp;!</b>{/if}
	</dd>
	{foreachelse}
	<dd>
		Vous n'êtes inscrit à aucune activité ou cotisation.
	</dd>
	{/foreach}
</dl>

<h2 class="ruler">Dettes et créances</h2>

{if !count($accounts)}
<p class="help">Aucune dette ou créance n'est associée à votre profil.</p>
{else}

<table class="list">
	<thead>
		<tr>
			<td class="money">Montant</td>
			<th>Compte</th>
			<td></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$accounts item="account"}
		<tr>
			<td class="money">{$account.balance|raw|money_currency}</td>
			<th>{$account.label}</th>
			<td>
				{if $account.position == Account::LIABILITY}<em>Nous vous devons {$account.balance|raw|money_currency}.</em>
				{else}<strong class="error">Vous nous devez {$account.balance|raw|money_currency}.</strong>{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}

{if $list->count()}

	<h2 class="ruler">Historique des inscriptions</h2>

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				<th>{$row.label}</th>
				<td>{$row.fee}</td>
				<td>{$row.date|date_short}</td>
				<td>{$row.expiry|date_short}</td>
				<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
				<td>{$row.amount|raw|money_currency}</td>
				<td class="actions">
				</td>
			</tr>
		{/foreach}

		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{/if}

{$snippets|raw}

{include file="_foot.tpl"}