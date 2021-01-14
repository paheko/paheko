{include file="admin/_head.tpl" title="Dépôt en banque : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

<p class="help">
	Cocher les cases correspondant aux montants à déposer, une nouvelle écriture sera générée.
</p>

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<table class="list">
		<thead>
			<tr>
				<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
				<td></td>
				<td>Date</td>
				<td>Réf. écriture</td>
				<td>Réf. ligne</td>
				<th>Libellé</th>
				<td class="money">Montant</td>
				<td class="money">Solde cumulé</td>
			</tr>
		</thead>
		<tbody>
			{foreach from=$journal item="line"}
			{if isset($line.sum)}
			<tr>
				<td colspan="5"></td>
				<td class="money">{if $line.sum > 0}-{/if}{$line.sum|abs|raw|html_money:false}</td>
				<th>Solde au {$line.date|date_short}</th>
				<td colspan="2"></td>
			</tr>
			{else}
			<tr>
				<td class="check">
					{input type="checkbox" name="deposit[%d]"|args:$line.id value="1" data-debit=$line.debit|abs data-credit=$line.credit default=$line.checked}
				</td>
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
				<td>{$line.date|date_short}</td>
				<td>{$line.reference}</td>
				<td>{$line.line_reference}</td>
				<th>{$line.label}</th>
				<td class="money">{$line.debit|raw|html_money}</td>
				<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|html_money:false}</td>
			</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>

	<fieldset>
		<legend>Détails de l'écriture de dépôt</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1 default="Dépôt en banque"}
			{input type="date" name="date" default=$date label="Date" required=1}
			{input type="money" name="amount" label="Montant" required=1}
			{input type="list" target="acc/charts/accounts/selector.php?chart=%d&targets=%d"|args:$account.id_chart,$target name="account_transfer" label="Compte de dépôt" required=1}
			{input type="text" name="reference" label="Numéro de pièce comptable"}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_deposit_%s"|args:$account.id}
		{button type="submit" name="save" label="Enregistrer" class="main" shape="check"}
	</p>
</form>

{literal}
<script type="text/javascript">
var total = 0;
$('tbody input[type=checkbox]').forEach((e) => {
	e.addEventListener('change', () => {
		var v = e.getAttribute('data-debit') || e.getAttribute('data-credit');
		v = parseInt(v, 10);
		total += e.checked ? v : -v;
		if (total < 0) {
			total = 0;
		}
		$('#f_amount').value = g.formatMoney(total);
	});
});

$('#f_all').addEventListener('change', (e) => {
	$('#f_amount').value = '';
	total = 0;
});
</script>
{/literal}

{include file="admin/_foot.tpl"}