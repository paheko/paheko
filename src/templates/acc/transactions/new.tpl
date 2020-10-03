{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new" js=1}

{include file="acc/_year_select.tpl" only_open=true}

<form method="post" action="{$self_url_no_qs}" enctype="multipart/form-data">
	{form_errors}

	{if $ok}
		<p class="confirm">
			L'opération numéro <a href="details.php?id={$ok}">{$ok}</a> a été ajoutée.
			(<a href="details.php?id={$ok}">Voir l'opération</a>)
		</p>
	{/if}

	<fieldset>
		<legend>Type d'écriture</legend>
		<dl>
			{input type="radio" name="type" value="revenue" label="Recette"}
			{input type="radio" name="type" value="expense" label="Dépense"}
			{input type="radio" name="type" value="transfer" label="Virement" help="Faire un virement entre comptes, déposer des espèces en banque, etc."}
			{input type="radio" name="type" value="debt" label="Dette" help="Quand l'association doit de l'argent à un membre ou un fournisseur"}
			{input type="radio" name="type" value="credit" label="Créance" help="Quand un membre ou un fournisseur doit de l'argent à l'association"}
			{input type="radio" name="type" value="advanced" label="Saisie avancée" help="Choisir les comptes du plan comptable, ventiler une écriture sur plusieurs comptes, etc."}
		</dl>
	</fieldset>

	<fieldset data-types="transfer">
		<legend>Virement</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="transfer_from" label="De" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="transfer_to" label="Vers" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="revenue">
		<legend>Recette</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=revenue"|args:$admin_url name="revenue_from" label="Type de recette" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="revenue_to" label="Compte d'encaissement" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="expense">
		<legend>Dépense</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=expense"|args:$admin_url name="expense_to" label="Type de dépense" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="expense_from" label="Compte de décaissement" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="debt">
		<legend>Dette</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=thirdparty"|args:$admin_url name="debt_from" label="Compte de tiers" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=expense"|args:$admin_url name="debt_to" label="Type de dette (dépense)" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="credit">
		<legend>Créance</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=thirdparty"|args:$admin_url name="credit_to" label="Compte de tiers" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=revenue"|args:$admin_url name="credit_from" label="Type de créance (recette)" required=1}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1}
			{input type="date" name="date" default=$date label="Date" required=1}
		</dl>
		<dl data-types="all-but-advanced">
			{input type="money" name="amount" label="Montant" required=1}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
		</dl>
	</fieldset>

	{* Saisie avancée *}
	<fieldset data-types="advanced">
		{include file="acc/transactions/_lines_form.tpl"}
	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input type="list" multiple=true name="users" label="Membres associés" target="%smembres/selector.php"|args:$admin_url}
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
		g.toggle('[data-types=' + v + ']', true);
		g.toggle('[data-types=all-but-advanced]', v != 'advanced');
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