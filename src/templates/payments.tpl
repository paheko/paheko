{include file="_head.tpl" title="Bonjour %s !"|args:$logged_user->name() current="payments"}

<h2>Providers</h2>

<ul>
{foreach from=$providers item='provider'}
	<li>{$provider->name}: {$provider->label}</li>
{/foreach}
</ul>

<h2>Liste des paiements</h2>

<table class="list">
	<thead>
		<tr>
			<td class="{if $list->order === 'label'}cur{/if}">Libellé</td>
			<td class="{if $list->order === 'status'}cur{/if}">Statut</td>
			<td class="{if $list->order === 'amount'}cur{/if}">Montant</td>
		</tr>
	</thead>
	<tbody>

	{foreach from=$payments item='payment'}
	<tr>
		<td><a href="{$admin_url}payments.php?id={$payment->id}">{$payment->label}</a></td>
		<td>{$payment.status}</td>
		<td>{$payment->amount|escape|money_currency}</td>
	</tr>
	{/foreach}

	</tbody>
</table>

{if $_GET.ok}
	<p class="confirm block">Paiement enregistré avec succès</p>
{/if}

<h2 class="ruler">Créer un paiement</h2>

<form method="POST" action="{$self_url}">
	<fieldset>
		<legend>Paiement</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=true}
			{input type="select" name="type" label="Type" options=Entities\Payments\Payment::TYPES default=Entities\Payments\Payment::UNIQUE_TYPE required=true}
			{input type="select" name="method" label="Méthode" options=Entities\Payments\Payment::METHODS required=true}
			{input type="select" name="provider" label="Prestataire" options=$provider_options default=Payments\Providers::MANUAL_PROVIDER required=true}
			{input type="list" name="author" label="Payeur/euse" target="!users/selector.php" can_delete="true" required=true}
			{input type="text" name="reference" label="Référence"}
			{input type="money" name="amount" label="Montant" required=true}

			<dt><strong>Comptabilité</strong></dt>
			{input name="accounting" type="checkbox" value="1" label="Enregistrer en comptabilité" default=false}
			<dd class="help">Laissez cette case décochée si vous n'utilisez pas Paheko pour la comptabilité.</dd>
		</dl>
	</fieldset>

	<fieldset class="accounting">
		<legend>Enregistrer en comptabilité</legend>
		{if !count($years)}
			<p class="error block">Il n'y a aucun exercice ouvert dans la comptabilité, il n'est donc pas possible d'enregistrer les activités dans la comptabilité. Merci de commencer par <a href="{$admin_url}acc/years/new.php">créer un exercice</a>.</p>
		{else}
		<dl>
			<dt><label for="f_id_year">Exercice</label> <b>(obligatoire)</b></dt>
			<dd>
				<select id="f_id_year" name="id_year">
					<option value="">-- Sélectionner un exercice</option>
					{foreach from=$years item="year"}
					<option value="{$year.id}">{$year.label} — {$year.start_date|date_short} au {$year.end_date|date_short}</option>
					{/foreach}
				</select>
			</dd>
			{input type="list" target="!acc/charts/accounts/selector.php?targets=%s"|args:'6' name="credit" label="Type de recette" required=1}
			{input type="list" target="!acc/charts/accounts/selector.php?targets=%s"|args:'1:2:3' name="debit" label="Compte d'encaissement" required=1}
			{input type="textarea" name="notes" label="Remarques" rows="4" cols="30"}
		</dl>
		{/if}
	</fieldset>

	{**** ToDo: add csrf token ****}
	{button type="submit" name="save" label="Créer" class="main"}
</form>

<script type="text/javascript">
{literal}
(function () {
	g.toggle('.accounting', $('#f_accounting_1').checked);

	$('#f_accounting_1').onchange = () => { g.toggle('.accounting', $('#f_accounting_1').checked); };

	function toggleYearForSelector()
	{
		var btn = document.querySelector('#f_account_container button');
		btn.value = btn.value.replace(/year=\d+/, 'year=' + y.value);

		let v = btn.parentNode.querySelector('span');
		if (v) {
			v.parentNode.removeChild(v);
		}
	}

	var y = $('#f_id_year')

	y.onchange = toggleYearForSelector;
})();
{/literal}
</script>

{include file="_foot.tpl"}