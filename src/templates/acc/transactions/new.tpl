{include file="admin/_head.tpl" title="Saisie d'une écriture" current="acc/new" js=1}

<form method="post" action="{$self_url}" enctype="multipart/form-data">
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
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="from" label="De" required=1}
			{input type="list" target="%sacc/accounts/selector.php?target=common"|args:$admin_url name="to" label="Vers" required=1}
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
		<table class="list transaction-lines">
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
			{foreach from=$lines item="line"}
				<tr>
					<th>{input type="list" target="%sacc/accounts/selector.php?target=all"|args:$admin_url name="lines[account][]" value=$line.account}</th>
					<td>{input type="money" name="lines[debit][]" value=$line.debit size=5}</td>
					<td>{input type="money" name="lines[credit][]" value=$line.credit size=5}</td>
					<td>{input type="text" name="lines[reference][]" value=$line.label size=10}</td>
					<td>{input type="text" name="lines[label][]"}</td>
					<td>{button label="Enlever la ligne" shape="minus" name="remove_line"}</td>
				</tr>
			{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<th></th>
					<td>{input type="money" name="debit_total" readonly="readonly" tabindex="-1" }</td>
					<td>{input type="money" name="credit_total" readonly="readonly" tabindex="-1" }</td>
					<td colspan="2" id="lines_message"></td>
					<td>{button label="Ajouter une ligne" shape="plus"}</td>
				</tr>
			</tfoot>
		</table>
	</fieldset>

	<fieldset>
		<legend>Détails</legend>
		<dl>
			{input type="list" multiple=true name="users" label="Membre associé" target="%smembres/selector.php"|args:$admin_url}
			{input type="text" name="reference" label="Numéro de pièce comptable"}
			{input type="textarea" name="notes" label="Remarques" rows=4 cols=30}

			{input type="file" name="file" label="Fichier joint"}

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
<script type="text/javascript">

function initForm() {
	// Hide type specific parts of the form
	function hideAllTypes() {
		var sections = $('fieldset[data-types]');

		sections.forEach((e) => {
			e.style.display = 'none';
		});
	}

	// Toggle parts of the form when a type is selected
	function selectType(v) {
		hideAllTypes();
		$('[data-types=' + v + ']')[0].style.display = 'block';
		$('[data-types=all-but-advanced]')[0].style.display = v == 'advanced' ? 'none' : 'block';
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

	// Advanced transaction: line management
	var lines = $('.transaction-lines tbody tr');

	function initLine(row) {
		row.querySelector('button[name="remove_line"]').onclick = () => {
			var count = $('.transaction-lines tbody tr').length;

			if (count <= 2) {
				alert("Il n'est pas possible d'avoir moins de deux lignes dans une écriture.");
				return false;
			}

			row.parentNode.removeChild(row);
			updateTotals();
		};

		// Update totals and disable other amount input
		var money_inputs = row.querySelectorAll('input.money');

		money_inputs.forEach((i) => {
			i.onkeyup = (e) => {
				if (!e.key.match(/^([0-9,.]|Separator|Backspace)$/i)) {
					return true;
				}

				var v = i.value.replace(/[^0-9.,]/);
				var ro = (v.length == 0 || v == 0) ? false : true;

				money_inputs.forEach((i2) => {
					i2.readOnly = i2 === i ? false : ro;
					if (i2 !== i) { i2.value = ''; }
				});

				updateTotals();
			};
		});
	}

	lines.forEach(initLine);

	function updateTotals() {
		var amounts = $('.transaction-lines tbody input.money');
		var debit = credit = 0;

		amounts.forEach((i) => {
			var v = i.value.replace(/[^0-9.,]/, '');
			if (v.length == 0) return;

			v = v.split(/[,.]/);
			var d = v.length == 2 ? v[1] : '0';
			v = v[0] + (d + '00').substr(0, 2);
			v = parseInt(v, 10);

			if (i.name.match(/debit/)) {
				debit += v;
			}
			else {
				credit += v;
			}
		});

		$('#lines_message').innerHTML = (debit === credit) ? '' : '<span class="alert">Écriture non équilibrée</span>';

		debit = debit ? debit + '' : '000';
		credit = credit ? credit + '' : '000';
		$('#f_debit_total').value = debit.substr(0, debit.length-2) + ',' + debit.substr(-2);
		$('#f_credit_total').value = credit.substr(0, credit.length-2) + ',' + credit.substr(-2);
	}

	// Add row button
	$('.transaction-lines tfoot button')[0].onclick = () => {
		var line = $('.transaction-lines tbody tr')[0];
		var n = line.cloneNode(true);
		n.querySelectorAll('input').forEach((e) => {
			e.value = '';
		});
		n.querySelector('.input-list .label').innerHTML = '';
		var b = n.querySelector('.input-list button');
		b.onclick = () => {
			g.current_list_input = b.parentNode;
			g.openFrameDialog(b.value);
			return false;
		};
		initLine(n);
		line.parentNode.appendChild(n);
	};
}

initForm();
</script>
{/literal}

{include file="admin/_foot.tpl"}