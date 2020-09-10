{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new" js=1}

<form method="post" action="{$self_url}">
	{form_errors}

	{if $ok}
		<p class="confirm">
			L'opération numéro <a href="{$admin_url}compta/operations/voir.php?id={$ok}">{$ok}</a> a été ajoutée.
			(<a href="{$admin_url}compta/operations/voir.php?id={$ok}">Voir l'opération</a>)
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
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="from" label="De" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="to" label="Vers" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="revenue">
		<legend>Recette</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=revenue"|args:$admin_url name="from" label="Type de recette" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="to" label="Compte d'encaissement" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="expense">
		<legend>Dépense</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=expense"|args:$admin_url name="to" label="Type de dépense" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="from" label="Compte de décaissement" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="debt">
		<legend>Dette</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=thirdparty"|args:$admin_url name="to" label="Compte de tiers" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=expense"|args:$admin_url name="from" label="Type de dette (dépense)" required=1}
		</dl>
	</fieldset>

	<fieldset data-types="credit">
		<legend>Créance</legend>
		<dl>
			{input type="list" target="%sacc/accounts/selector.php?target=thirdparty"|args:$admin_url name="to" label="Compte de tiers" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=revenue"|args:$admin_url name="from" label="Type de créance (recette)" required=1}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations</legend>
		<dl>
			{input type="text" name="label" label="Libellé" required=1}
			{input type="date" name="date" value=$date label="Date" required=1}
		</dl>
		<dl data-types="all-but-advanced">
			{input type="number" name="amount" label="Montant (%s)"|args:$config.monnaie min="0.00" step="0.01" value="0.00" required=1}
			{input type="text" name="reference_paiement" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
		</dl>
	</fieldset>



	{* Saisie avancée *}
	<fieldset data-types="advanced">
		<table class="list">
			<thead>
				<tr>
					<th>Compte</th>
					<td>Débit</td>
					<td>Crédit</td>
					<td>Réf. pièce</td>
					<td>Libellé ligne</td>
					<td></td>
				</tr>
			</thead>
			<tbody>
			{foreach from=$lines key="line_number" item="line"}
				<tr>
					<th>{input type="list" target="%sacc/accounts/selector.php?target=all"|args:$admin_url name="lines[%d][account]"|args:$line_number value=$line.id_account required=1}</th>
					<td>{input type="number" name="lines[%d][debit]"|args:$line_number min="0.00" step="0.01" value=$line.debit required=1}</td>
					<td>{input type="number" name="lines[%d][credit]"|args:$line_number min="0.00" step="0.01" value=$line.credit required=1}</td>
					<td>{input type="text" name="lines[%d][reference]" size=8}</td>
					<td>{input type="text" name="lines[%d][label]"}</td>
					<td></td>
				</tr>
			{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<th></th>
					<td id="lines_debit_total"></td>
					<td id="lines_credit_total"></td>
					<td colspan="2"></td>
					<td></td>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input type="list" multiple=true name="membre" label="Membres associés" target="%smembres/selector.php"|args:$admin_url}
			{input type="text" name="numero_piece" label="Numéro de pièce comptable"}
			{input type="textarea" name="remarques" label="Remarques" rows=4 cols=30}

			{if count($analytical_accounts) > 0}
				{input type="select" name="analytical_account" label="Compte analytique (projet)" options=$analytical_accounts}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="compta_saisie"}
		<input type="submit" name="save" value="Enregistrer &rarr;" />
	</p>

</form>

{literal}
<script type="text/javascript">

function initForm() {
	function hideAll() {
		var sections = $('fieldset[data-types]');

		sections.forEach((e) => {
			e.style.display = 'none';
		});
	}

	var radios = $('fieldset input[type=radio][name=type]');

	radios.forEach((e) => {
		e.onchange = (evt) => {
			hideAll();
			$('[data-types=' + e.value + ']')[0].style.display = 'block';
			$('[data-types=all-but-advanced]')[0].style.display = e.value == 'advanced' ? 'none' : 'block';
		};
	});

	hideAll();
}

function inputListSelected(value, label) {
	var i = g.current_list_input;
	i.parentNode.querySelector('input[type=hidden]').value = value;
	i.parentNode.querySelector('span.label').innerHTML = label;
	g.closeDialog();
	i.focus();
}

initForm();
</script>
{/literal}

{include file="admin/_foot.tpl"}