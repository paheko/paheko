{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new" js=1}

{include file="acc/_year_select.tpl"}

<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">
	{form_errors}

	{if $ok}
		<p class="confirm">
			L'opération numéro <a href="details.php?id={$ok}">{$ok}</a> a été ajoutée.
			(<a href="details.php?id={$ok}">Voir l'opération</a>)
		</p>
	{/if}

	{if $payoff_for}
		<input type="hidden" name="type" value="{$transaction::TYPE_PAYOFF}" />
		<input type="hidden" name="payoff_for" value="{$payoff_for.id}" />
		<fieldset>
			<legend>{if $payoff_for->type == $transaction::TYPE_DEBT}Règlement de dette{else}Règlement de créance{/if}</legend>
			<dl>
				<dt>Écriture d'origine</dt>
				<dd><a class="num" href="{$admin_url}acc/transactions/details.php?id={$payoff_for.id}">#{$payoff_for.id}</a></dd>
				{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart=%d"|args:$payoff_targets,$chart_id name="account_payoff" label="Compte de règlement" required=1}
			</dl>
		</fieldset>
	{else}
		<fieldset>
			<legend>Type d'écriture</legend>
			<dl>
				{input type="radio" name="type" value=$transaction::TYPE_REVENUE default=-1 label="Recette"}
				{input type="radio" name="type" value=$transaction::TYPE_EXPENSE default=-1 label="Dépense"}
				{input type="radio" name="type" value=$transaction::TYPE_TRANSFER default=-1 label="Virement" help="Faire un virement entre comptes, déposer des espèces en banque, etc."}
				{input type="radio" name="type" value=$transaction::TYPE_DEBT default=-1 label="Dette" help="Quand l'association doit de l'argent à un membre ou un fournisseur"}
				{input type="radio" name="type" value=$transaction::TYPE_CREDIT default=-1 label="Créance" help="Quand un membre ou un fournisseur doit de l'argent à l'association"}
				{input type="radio" name="type" value=$transaction::TYPE_ADVANCED default=-1 label="Saisie avancée" help="Choisir les comptes du plan comptable, ventiler une écriture sur plusieurs comptes, etc."}
			</dl>
		</fieldset>

		{foreach from=$types item="type"}
			<fieldset data-types="t{$type.id}">
				<legend>{$type.label}</legend>
				<dl>
				{foreach from=$type.accounts key="key" item="account"}
					{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart=%d"|args:$account.targets,$chart_id name="account_%d_%d"|args:$type.id,$key label=$account.label required=1}
				{/foreach}
				</dl>
			</fieldset>
		{/foreach}
	{/if}

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1 source=$transaction}
			{input type="date" name="date" default=$date label="Date" required=1 source=$transaction}
		</dl>
		<dl data-types="all-but-advanced">
			{input type="money" name="amount" label="Montant" required=1 default=$amount}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc." source=$transaction}
		</dl>
	</fieldset>

	{* Saisie avancée *}
	<fieldset data-types="t{$transaction::TYPE_ADVANCED}">
		{include file="acc/transactions/_lines_form.tpl" chart_id=$current_year.id_chart}
	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="membres/selector.php"}
			{input type="text" name="reference" label="Numéro de pièce comptable"}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}

			{input type="file" name="file" label="Fichier joint"}
		</dl>
		<dl data-types="all-but-advanced">
			{if count($analytical_accounts) > 0}
				{input type="select" name="id_analytical" label="Compte analytique (projet)" options=$analytical_accounts}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_transaction_new"}
		<input type="submit" name="save" value="Enregistrer &rarr;" />
	</p>

</form>

{literal}
<script type="text/javascript" defer="defer" async="async">
function initForm() {
	// Hide type specific parts of the form
	function hideAllTypes() {
		g.toggle('fieldset[data-types]', false);
	}

	// Toggle parts of the form when a type is selected
	function selectType(v) {
		hideAllTypes();
		g.toggle('[data-types=t' + v + ']', true);
		g.toggle('[data-types=all-but-advanced]', v != <?=$transaction::TYPE_ADVANCED?>);
		// Disable required form elements, or the form won't be able to be submitted
		$('[data-types=all-but-advanced] input[required]').forEach((e) => {
			e.disabled = v == 'advanced' ? true : false;
		});

	}

	var radios = $('fieldset input[type=radio][name=type]');

	radios.forEach((e) => {
		e.onchange = () => {
			selectType(e.value);
		};
	});

	hideAllTypes();

	// In case of a pre-filled form: show the correct part of the form
	var current = document.querySelector('input[name=type]:checked');
	if (current) {
		selectType(current.value);
	}
}

initForm();

g.script('scripts/accounting.js', () => { initTransactionForm(); });
</script>
{/literal}

{include file="admin/_foot.tpl"}