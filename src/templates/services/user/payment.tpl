{include file="_head.tpl" title="Enregistrer un règlement" current="users/services"}

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
			{input type="list" target="!acc/charts/accounts/selector.php?types=%s&id_year=%d"|args:$account_types:$fee.id_year name="account_selector" label="Compte de règlement" required=1}
			{input type="text" name="reference" label="Numéro de pièce comptable" help="Numéro de facture, de reçu, de note de frais, etc."}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
			{if count($projects) > 0}
				{input type="select" options=$projects name="id_project" label="Projet analytique" default=$fee.id_project required=false default_empty="— Aucun —"}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}