{include file="_head.tpl" title="Importer dans %s — %s"|args:$account.code:$account.label current="acc/accounts"}

<nav class="acc-year">
	<h4>Exercice sélectionné&nbsp;:</h4>
	<h3>{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</h3>
</nav>

{form_errors}

{if $csv->ready()}
	<form method="post" action="{$self_url}">
		<table class="list import">
			<thead>
				<tr>
					<td>Importer</td>
					<td>Date</td>
					<td>Montant</td>
					<th width="30%">Libellé</th>
					<td width="20%">Compte</td>
					<td>Pièce comptable</td>
					<td>Réf. paiement</td>
					<td>Rapprocher</td>
				</tr>
			</thead>
			<tbody>
				<?php $new_transactions = false; ?>
				{foreach from=$transactions item="t" key="i"}
				<tr class="{if empty($import[$i])}disabled{/if}">
					{if $t->exists()}
						<td class="num">{link label=$t.id target="_blank" href="!acc/transactions/details.php?id=%d"|args:$t.id}</td>
						<td>{$t.date|date_short}</td>
						<td class="money">{$t->getSumForAccount($account->id)|money_currency_html|raw}</td>
						<th>{$t.label}</th>
						<td><?=nl2br(htmlspecialchars(implode("\n", $t->listAccountsAssoc($account->id))))?></td>
						<td>{$t.reference}</td>
						<td>{$t->getPaymentReference()}</td>
						<td></td>
					{else}
						<?php
						$reconciled = $t->hasReconciledLines() ? 1 : 0;
						$new_transactions = true;
						$enabled = !isset($_POST['t']) || !empty($_POST['t'][$i]['import']);
						$selected_account = $_POST['t'][$i]['account'] ?? null;
						$sum = $t->getSumForAccount($account->id) * -1;
						$types = [
							$account::TYPE_CASH,
							$account::TYPE_BANK,
							$account::TYPE_OUTSTANDING,
							$sum < 0 ? $account::TYPE_EXPENSE : $account::TYPE_REVENUE,
						];
						$types = rawurlencode(implode('|', $types));
						?>
						<td class="checkbox">{input type="checkbox" name="t[%d][import]"|args:$i value="1" default=$enabled}</td>
						<td>{$t.date|date_short}</td>
						<td class="money">{$sum|money_currency_html|raw}</td>
						<th>{input type="text" required=true name="t[%d][label]"|args:$i default=$t.label class="full-width"}</th>
						<td>{input type="list" required=true name="t[%d][account]"|args:$i target="!acc/charts/accounts/selector.php?types=%s"|args:$types default=$selected_account}</td>
						<td>{input type="text" name="t[%d][reference]"|args:$i default=$t.reference size=15}</td>
						<td>{input type="text" name="t[%d][payment_ref]"|args:$i default=$t->getPaymentReference() size=15}</td>
						<td class="checkbox">{input type="checkbox" name="t[%d][reconcile]"|args:$i value=1 default=$reconciled}</td>
					{/if}
				</tr>
				{/foreach}
			</tbody>
		</table>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{linkbutton href="?id=%d&cancel=1"|args:$account.id label="Annuler" shape="left"}
			{if $new_transactions}
			{button type="submit" name="save" label="Importer" class="main" shape="upload"}
			{/if}
		</p>
	</form>

	<script type="text/javascript">
	{literal}
	function toggleRow(checkbox, row) {
		row.classList.toggle('disabled', !checkbox.checked);
		row.querySelectorAll('input, button').forEach(e => e.disabled = e !== checkbox && !checkbox.checked);
	}

	$('tbody tr').forEach(row => {
		var check = row.querySelector('input[type=checkbox][name*=import]');

		if (!check) {
			return;
		}

		check.onchange = () => toggleRow(check, row);
		toggleRow(check, row);
	});
	{/literal}
	</script>
{elseif $csv->loaded() && !$csv->isSheetSelected()}
	<form method="post" action="{$self_url}">
		{include file="common/_csv_select_sheet.tpl"}

		<p class="submit">
			{csrf_field key=$csrf_key}
			{linkbutton href="?id=%d&cancel=1"|args:$account.id label="Annuler" shape="left"}
			{button type="submit" name="set_sheet" label="Continuer" class="main" shape="right"}
		</p>
	</form>

{elseif $csv->loaded()}
	<form method="post" action="{$self_url}">
		{include file="common/_csv_match_columns.tpl"}

		<p class="submit">
			{csrf_field key=$csrf_key}
			{linkbutton href="?id=%d&cancel=1"|args:$account.id label="Annuler" shape="left"}
			{button type="submit" name="set_columns" label="Continuer" class="main" shape="right"}
		</p>
	</form>
{else}

	<div class="block help">
		<h3>Cette page permet d'importer un relevé bancaire provenant du site de votre banque.</h3>
		<p>Les opérations présentes dans le relevé pourront être transformées en écritures comptables.</p>
		<p>Cela permet de créer rapidement toutes les écritures liées au compte bancaire.</p>
		<p>Pour automatiser la reconnaissance des écritures, il est aussi possible de <a href="rules/">définir des règles d'import</a>.</p>
	</div>

	<form method="post" action="{$self_url}" enctype="multipart/form-data">

		<fieldset>
			<legend>Importer un fichier</legend>
			<dl>
				{input type="file" name="file" label="Fichier à importer" accept="csv+ofx+qif" required=true}
				{include file="common/_csv_help.tpl" csv=$csv more_text="Si le fichier comporte des écritures dont la date est en dehors de l'exercice courant, elles seront ignorées."}
			</dl>
			<p>
				{linkbutton shape="settings" label="Configurer les règles d'import" href="rules/"}
			</p>
		</fieldset>

		<p class="help">
			Il sera possible de modifier les libellés et comptes d'affectation des écritures à l'étape suivante.
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{linkbutton href="./" label="Annuler" shape="left"}
			{button type="submit" name="load" label="Charger le fichier" shape="right" class="main"}
		</p>

	</form>

{/if}


{include file="_foot.tpl"}