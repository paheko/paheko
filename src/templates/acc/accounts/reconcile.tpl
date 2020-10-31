{include file="admin/_head.tpl" title="Rapprochement : %s — %s"|args:$account.code,$account.label current="acc/accounts" js=1}

<form method="get" action="{$self_url_no_qs}">
	{if !empty($prev) && !empty($next)}
	<fieldset class="shortFormRight">
		<legend>Rapprochement par mois</legend>
		<dl>
			<dd class="actions">
			<a class="icn" href="{$self_url_no_qs}?id={$account.id}&amp;start={$prev|date_fr:'Y-m-01'}&amp;end={$prev|date_fr:'Y-m-t'}{if qg('sauf')}&amp;sauf=1{/if}">&larr; {$prev|date_fr:'F Y'}</a>
			| <a class="icn" href="{$self_url_no_qs}?id={$account.id}&amp;start={$next|date_fr:'Y-m-01'}&amp;end={$next|date_fr:'Y-m-t'}{if qg('sauf')}&amp;sauf=1{/if}">{$next|date_fr:'F Y'} &rarr;</a>
			</dd>
		</dl>
	</fieldset>
	{/if}
	<fieldset>
		<legend>Période de rapprochement</legend>
		<p>
			Du
			{input type="date" name="start" default=$start}
			au
			{input type="date" name="end" default=$end}
			<input type="hidden" name="id" value="{$account.id}" />
			<input type="submit" value="Afficher" />
		</p>
	</fieldset>
</form>

<p class="block alert">
	<strong>Attention&nbsp;!</strong>
	Afin de simplifier les choses, les écritures apparaissent ici dans le sens de la banque, à l'inverse des journaux comptables.
</p>

{form_errors}

<form method="post" action="{$self_url}">
	<table class="list">
		<thead>
			<tr>
				<td class="check"><input type="checkbox" title="Tout cocher / décocher" /></td>
				<td></td>
				<td>Date</td>
				<td class="money">Débit</td>
				<td class="money">Crédit</td>
				<td class="money">Solde cumulé</td>
				<th>Libellé</th>
				<th>Réf. écriture</th>
				<th>Réf. ligne</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$journal item="line"}
			{if isset($line.sum)}
			<tr>
				<td colspan="5"></td>
				<td class="money">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|html_money:false}</td>
				<th>Solde au {$line.date|date_fr:'d/m/Y'}</th>
				<td colspan="2"></td>
			</tr>
			{else}
			<tr>
				<td class="check"><input type="checkbox" name="reconcile[{$line.id_line}]" value="1" {if $line.reconciled}checked="checked"{/if} /></td>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_fr:'d/m/Y'}</td>
				<td class="money">{$line.credit|raw|html_money}</td>
				<td class="money">{$line.debit|raw|html_money}</td> {* Not a bug! Credit/debit is reversed here to reflect the bank statement *}
				<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|html_money:false}</td>
				<th>{$line.label}</th>
				<td>{$line.reference}</td>
				<td>{$line.line_reference}</td>
			</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>
	<p class="submit">
		{csrf_field key="acc_reconcile_%s"|args:$account.id}
		<input type="submit" name="save" value="Enregistrer" />
		<input type="submit" name="save_next" value="Enregistrer et aller au mois suivant &rarr;" class="minor" />
	</p>
</form>

{include file="admin/_foot.tpl"}