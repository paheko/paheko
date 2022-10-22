{include file="admin/_head.tpl" title="Enregistrer un règlement" current="membres/services"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Enregistrer un règlement</legend>

		<dl>
			<dt>Membre sélectionné</dt>
			<dd><h3>{$user_name}</h3></dd>
			<dt><strong>Inscription</strong></dt>
			{input type="checkbox" name="paid" value="1" default=$su.paid label="Marquer cette inscription comme payée"}
			{input type="date" name="date" label="Date" required=1 source=$su}
			{input type="money" name="amount" label="Montant réglé par le membre" required=1}
			{input type="list" target="!acc/charts/accounts/selector.php?targets=%s"|args:$account_targets name="account_selector" label="Compte de règlement" required=1}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de reçu, de note de frais, etc."}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
			{if count($analytical_accounts) > 1}
			{input type="select" options=$analytical_accounts name="id_analytical" label="Projet analytique" default=$fee.id_analytical required=false}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}