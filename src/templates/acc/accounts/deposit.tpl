{include file="_head.tpl" title="Dépôt en banque : %s — %s"|args:$account.code,$account.label current="acc/accounts"}

{if $year.id !== $current_year.id}
	<p class="alert block">
		Note : les montants à déposer proviennent d'un précédent exercice ({$year->getLabelWithYearsAndStatus()}).
	</p>
{/if}

{form_errors}

{if $missing_balance > 0}
<p class="alert block">
	Il existe une différence de {$missing_balance|raw|money_currency} entre la liste des écritures à déposer
	et le solde du compte.<br />
	Cette situation est généralement dûe à des écritures de dépôt qui ont été supprimées.<br />
	{linkbutton shape="plus" label="Faire un virement pour régulariser" href="!acc/transactions/new.php?0=%d&l=Régularisation%%20dépôt&account=%d"|args:$missing_balance,$account.id}
</p>
{/if}

{if !$journal->count()}
	<p class="alert block">Il n'y a aucune écriture qui nécessiterait un dépôt.
	</p>
{else}
	<p class="help">
		Cocher les cases correspondant aux montants à déposer, une nouvelle écriture sera générée.
	</p>

	<form method="post" action="{$self_url}" data-focus="1">
		{include file="common/dynamic_list_head.tpl" check=true list=$journal}

		{foreach from=$journal->iterate() item="line"}
				<tr>
					<td class="check">
						{input type="checkbox" name="deposit[%d]"|args:$line.id_line value="1" data-debit=$line.debit|abs data-credit=$line.credit default=$line.checked}
					</td>
					<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
					<td>{$line.date|date_short}</td>
					<td>{$line.reference}</td>
					<td>{$line.line_reference}</td>
					<th>{$line.label}</th>
					<td class="money">{$line.debit|raw|money}</td>
					<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|money:false}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>

		<fieldset>
			<legend>Détails de l'écriture de dépôt</legend>
			<dl>
				<dt><strong>Nombre de chèques</strong></dt>
				<dd><mark id="cheques_count">0</mark></dd>
				{input type="text" name="label" label="Libellé" required=1 default="Dépôt en banque"}
				{input type="date" name="date" default=$date label="Date" required=1}
				{input type="money" name="amount" label="Montant" required=1}
				{input type="list" target="!acc/charts/accounts/selector.php?id_chart=%d&types=%d"|args:$account.id_chart:$types name="account_transfer" label="Compte de dépôt" required=1}
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
	var count = 0;
	$('tbody input[type=checkbox]').forEach((e) => {
		e.addEventListener('change', () => {
			var v = e.getAttribute('data-debit') || e.getAttribute('data-credit');
			v = parseInt(v, 10);
			total += e.checked ? v : -v;
			count += e.checked ? 1 : -1;
			if (total < 0) {
				total = 0;
			}
			$('#f_amount').value = g.formatMoney(total);
			$('#cheques_count').innerText = count;
		});
	});

	$('#f_all').addEventListener('change', (e) => {
		$('#f_amount').value = '';
		total = 0;
	});
	</script>
	{/literal}
{/if}

{include file="_foot.tpl"}