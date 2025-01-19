{include file="_head.tpl" title="Balance d'ouverture" current="acc/years"}

{form_errors}

{if !empty($_GET.from) && empty($_POST)}
<p class="block confirm">
	L'exercice a bien été créé.
</p>
{/if}

{if $year_selected}
	{if $has_balance}
	<p class="block alert">
		<strong>Attention&nbsp;!</strong>
		Une balance d'ouverture existe déjà dans cet exercice.<br />
		En validant ce formulaire, les écritures de balance et d'affectation du résultat qui existent <strong>seront supprimées et remplacées</strong>.
	</p>
	{elseif $year->countTransactions()}
	<p class="block alert">
		<strong>Attention&nbsp;!</strong>
		Cet exercice a déjà des écritures, peut-être avez-vous déjà renseigné manuellement la balance d'ouverture&nbsp;?
	</p>
	{/if}
{/if}


<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Exercice&nbsp;: «&nbsp;{$year.label}&nbsp;» du {$year.start_date|date_short} au {$year.end_date|date_short}</legend>

	{if !$year_selected}
		<dl>
			<dt><label for="f_from_year">Reporter les soldes de fermeture d'un exercice</label></dt>
			<dd class="help">Pour reprendre les soldes des comptes de l'exercice précédent.</dd>
			<dd>
				<select id="f_from_year" name="from_year">
					{foreach from=$years item="year"}
						<option value="{$year.id}"{if $year.id == $_GET.from} selected="selected"{/if} data-open="{$year->isOpen()}">{$year->getLabelWithYearsAndStatus()}</option>
					{/foreach}
					<option value="">— Saisie manuelle —</option>
				</select>
			</dd>
			<dd class="hidden warn-not-closed">
				<p class="alert block">Attention l'exercice sélectionné n'est pas clôturé&nbsp;!<br />Si vous modifiez cet exercice après avoir validé cette balance d'ouverture, celle-ci pourrait ne plus correspondre au bilan de l'exercice précédent&nbsp;!</p>
			</dd>
		</dl>
		{literal}
		<script type="text/javascript" async="async">
		let s = document.querySelector('#f_from_year');
		const checkOpen = function() {
			let v = s.options[s.selectedIndex].dataset.open;
			g.toggle('.warn-not-closed', v === 'open' ? true : false);
		};
		s.onchange = checkOpen;
		checkOpen();
		</script>
		{/literal}
	{else}
		<p class="help">
			Renseigner ici les soldes d'ouverture (débiteur ou créditeur) des comptes.
		</p>
		{if !empty($_GET.from)}
		<p class="help">
			Normalement il suffit de valider ce formulaire pour faire le report à nouveau des soldes de comptes.
		</p>
		{/if}
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
						{input type="list" target="!acc/charts/accounts/selector.php?id_chart=%d"|args:$year.id_chart name="lines[account_selector][]" default=$line.account_selector}
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
		{if $can_appropriate}
		<dl>
			{input type="checkbox" name="appropriation" value="1" checked="checked" label="Affecter automatiquement le résultat (conseillé)"}
			<dd class="help">Si cette case est cochée, le résultat sera automatiquement affecté au compte « {$appropriation_account.code} — {$appropriation_account.label} ».</dd>
		</dl>
		{/if}
	{/if}
	</fieldset>

	<p class="submit">
		{if null === $previous_year}
			{button type="submit" name="next" label="Continuer" shape="right" class="main"}
			— ou —
			{linkbutton shape="reset" href="!acc/years/" label="Passer cette étape"}
			<br />
			<i class="help">(Il sera toujours possible de reprendre la balance d'ouverture plus tard.)</i>
		{else}
			{csrf_field key=$csrf_key}
			{if $previous_year}
				<input type="hidden" name="from_year" value="{$previous_year.id}" />
			{else}
				<input type="hidden" name="from_year" value="" />
			{/if}
			{if $year_selected}
				{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
			{else}
				{button type="submit" name="save" label="Continuer" shape="right" class="main"}
			{/if}
			{literal}
			<script type="text/javascript" defer="defer" async="async">
			g.script('scripts/accounting.js', () => { initTransactionForm(); });
			</script>
			{/literal}
		{/if}
	</p>

</form>


{include file="_foot.tpl"}