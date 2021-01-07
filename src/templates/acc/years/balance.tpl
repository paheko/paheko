{include file="admin/_head.tpl" title="Balance d'ouverture" current="acc/years"}

{form_errors}

{if $year->countTransactions()}
<p class="block alert">
	<strong>Attention&nbsp;!</strong>
	Cet exercice a déjà des écritures, peut-être avez-vous déjà renseigné la balance d'ouverture&nbsp;?
</p>
{/if}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Exercice&nbsp;: «&nbsp;{$year.label}&nbsp;» du {$year.start_date|date_short} au {$year.end_date|date_short}</legend>

		{if !$year_selected}
		<dl>
			<dt><label for="f_from_year">Reprendre les soldes de fermeture d'un exercice clôturé</label></dt>
			<dd>
				<select id="f_from_year" name="from_year">
					<option value="">-- Aucun</option>
					{foreach from=$years item="year"}
					<option value="{$year.id}">{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</option>
					{/foreach}
				</select>
			</dd>
		</dl>
		{else}
		<p class="help">
			Renseigner ici les soldes d'ouverture (débiteur ou créditeur) des comptes.
		</p>
		<table class="list transaction-lines">
			<thead>
				<tr>
					{if $chart_change}
						<td>Ancien compte</td>
						<th>Nouveau compte</th>
					{else}
						<th>Compte</th>
					{/if}
					<td>Débit</td>
					<td>Crédit</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
			{foreach from=$lines key="k" item="line"}
				<tr>
					{if $chart_change || isset($line->code, $line->label)}
						<td>
							{$line.code} — {$line.label}
							<input type="hidden" name="lines[code][]" value="{$line.code}" />
							<input type="hidden" name="lines[label][]" value="{$line.label}" />
						</td>
					{/if}
					<th>
						{input type="list" target="acc/charts/accounts/selector.php?chart=%d"|args:$year.id_chart name="lines[account][]" default=$line.account}
						{if !empty($line.message)}<span class="alert">{$line.message}</span>{/if}
					</th>
					<td>{input type="money" name="lines[debit][]" default=$line.debit size=5}</td>
					<td>{input type="money" name="lines[credit][]" default=$line.credit size=5}</td>
					<td>{button label="Enlever la ligne" shape="minus" min="1" name="remove_line"}</td>
				</tr>
			{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<th>Total</th>
					{if $chart_change}
						<td></td>
					{/if}
					<td>{input type="money" name="debit_total" readonly="readonly" tabindex="-1" }</td>
					<td>{input type="money" name="credit_total" readonly="readonly" tabindex="-1" }</td>
					<td>{button label="Ajouter une ligne" shape="plus"}</td>
				</tr>
			</tfoot>
		</table>
		{/if}
	</fieldset>

	<p class="submit">
		{if null === $previous_year}
			{button type="submit" name="next" label="Continuer" shape="right" class="main"}
			- ou -
			{linkbutton shape="reset" href="!acc/years/" label="Passer cet étape"} <i class="help">(Il sera toujours possible de reprendre la balance d'ouverture plus tard.)</i>
		{else}
			{csrf_field key="acc_years_balance_%s"|args:$year.id}
			{if $previous_year}
				<input type="hidden" name="from_year" value="{$previous_year.id}" />
			{else}
				<input type="hidden" name="from_year" value="" />
			{/if}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}

			{literal}
			<script type="text/javascript" defer="defer" async="async">
			g.script('scripts/accounting.js', () => { initTransactionForm(); });
			</script>
			{/literal}
		{/if}
	</p>

</form>


{include file="admin/_foot.tpl"}