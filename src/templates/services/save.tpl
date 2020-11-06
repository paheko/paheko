{include file="admin/_head.tpl" title="Enregistrer une activité" current="membres/services" js=1}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Enregistrer une activité</legend>
		<dl>
			{input type="list" name="user" required=1 label="Membre concerné" default=$selected_user target="membres/selector.php"}

			<dt><label for="f_service_ID">Activité</label> <b>(obligatoire)</b></dt>
			{foreach from=$grouped_services item="service"}
				{input type="radio" name="id_service" value=$service.id label=$service.label help=$service.description data-account=$service.id_account}
				<dd data-service="s{$service.id}">
					<dl>
						<dt><label for="f_fee">Tarif</label> <b>(obligatoire)</b></dt>
					{foreach from=$service.fees key="service_id" item="fee"}
						{input type="radio" name="id_fee" value=$fee.id label=$fee.label help=$fee.description data-amount=$fee.amount data-user-amount=$fee.user_amount}
					{/foreach}
					</dl>
				</dd>
			{/foreach}

			<dt><strong>Montant de l'activité à payer</strong></dt>
			<dd><h3 class="money warning" id="target_amount" data-currency="{$config.monnaie}">--</h3></dd>
		</dl>
		<dl class="accounting">
			{input type="money" name="paid_amount" label="Montant réglé par le membre"}
		</dl>
		<dl>
			{input type="checkbox" name="paid" value="1" label="Marquer cette activité comme payée"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		<input type="submit" name="save" value="Enregistrer &rarr;" />
	</p>

</form>

{literal}
<script type="text/javascript">
function selectService(elm) {
	$('[data-service]').forEach((e) => {
		e.style.display = ('s' + elm.value == e.getAttribute('data-service')) ? 'block' : 'none';
	});

	var first = document.querySelector('[data-service="s' + elm.value + '"] input[name=id_fee]');
	first.checked = true;
	selectFee(first);
}
function selectFee(elm) {
	var userAmount = parseInt(elm.getAttribute('data-user-amount'), 10);
	var amount = parseInt(elm.getAttribute('data-amount'), 10);

	if (userAmount) {
		amount = userAmount;
	}

	var a = $('#target_amount');

	if (amount) {
		a.innerHTML = g.formatMoney(amount) + ' ' + a.getAttribute('data-currency');
	}
	else {
		a.innerHTML = 'prix libre';
	}
}

$('input[name=id_service]').forEach((e) => {
	e.onchange = () => { selectService(e); };
});

$('input[name=id_fee]').forEach((e) => {
	e.onchange = () => { selectFee(e); };
});

var selected = document.querySelector('input[name="id_service"]:checked, input[name="id_service"]');
selected.checked = true;

selectService(selected);
</script>
{/literal}

{include file="admin/_foot.tpl"}