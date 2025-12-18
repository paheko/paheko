{include file="_head.tpl" title="Dépôt en banque : %s — %s"|args:$account.code:$account.label current="acc/accounts"}

{include file="acc/_year_select.tpl"}

{if $has_transactions_from_other_years}
<p class="actions">
	{if !$only_this_year}
		{linkbutton shape="eye-off" label="Cacher les écritures d'autres exercices" href="?id=%d&only=1"|args:$account.id}
	{else}
		{linkbutton shape="eye" label="Afficher les écritures de tous les exercices" href="?id=%d&only=0"|args:$account.id}
	{/if}
</p>
{/if}

{if isset($_GET['marked'])}
<p class="confirm block">
	Les lignes ont bien été marquées comme déposées.
</p>
{/if}

{form_errors}

{if $missing_balance > 0}
<p class="alert block">
	Il existe une différence de {$missing_balance|raw|money_currency} entre la liste des écritures à déposer
	et le solde du compte.<br />
	Il y a peut-être des chèques de l'exercice précédent qui n'ont pas été déposés&nbsp;?
</p>
{/if}

{if !$journal->count()}
	<p class="alert block">Il n'y a aucune écriture qui nécessiterait un dépôt.
	</p>
{else}
	<p class="help">
		Cocher les cases correspondant aux montants à déposer.
	</p>

	<form method="post" action="{$self_url_no_qs}?id={$account.id}" data-focus="1">
		{include file="common/dynamic_list_head.tpl" check=true list=$journal}

		{foreach from=$journal->iterate() item="line"}
				<tr>
					<td class="check">
						{input type="checkbox" name="deposit[%d]"|args:$line.id_line value="1" data-debit=$line.debit|abs data-credit=$line.credit default=$line.checked}
					</td>
					<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">#{$line.id}</a></td>
					<td>{$line.date|date_short}</td>
					{if !$only_this_year}
						<td>{$line.year_label}</td>
					{/if}
					<td>{$line.reference}</td>
					<td>{$line.line_reference}</td>
					<th scope="row">{$line.label}</th>
					<td class="money">{$line.debit|raw|money}</td>
					<td class="money">{if $line.running_sum > 0}-{/if}{$line.running_sum|abs|raw|money:false}</td>
					<td></td>
				</tr>
			{/foreach}
			</tbody>
		</table>

		<p class="help">
			<mark id="lines_count">0</mark> lignes sélectionnées<br />
			Total des montants : <mark id="lines_total">0</mark>
		</p>

		<p class="actions">
			{button type="submit" class="minor" name="mark" label="Marquer comme déposé" shape="check" value=1}
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="create" label="Renseigner le dépôt" class="main" shape="right"}
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
			$('#lines_total').innerText = g.formatMoney(total);
			$('#lines_count').innerText = count;
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