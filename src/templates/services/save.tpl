{include file="admin/_head.tpl" title="Enregistrer une activité" current="membres/services" js=1}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Enregistrer une activité</legend>
		<dl>
			{input type="list" name="user" required=1 label="Membre concerné" default=$selected_user target="membres/selector.php"}

			<dt><label for="f_service_ID">Activité</label> <b>(obligatoire)</b></dt>
			{foreach from=$grouped_services item="service"}
				<dd><label>
					<input type="radio" name="id_service" id="f_id_service_{$service.id}" value="{$service.id}" {if f('id_service') == $service->id}checked="checked"{/if} />
					{$service.label} —
					{if $service.duration}
						{$service.duration} jours
					{elseif $service.start_date}
						du {$service.start_date|date_short} au {$service.end_date|date_short}
					{else}
						ponctuelle
					{/if}
					</label>
				</dd>
				{if $service.description}
				<dd class="help">
					{$service.description|escape|nl2br}
				</dd>
				{/if}
				<dd data-service="s{$service.id}">
					<dl>
						<dt><label for="f_fee">Tarif</label> <b>(obligatoire)</b></dt>
					{foreach from=$service.fees key="service_id" item="fee"}
						{input type="radio" name="id_fee" value=$fee.id label=$fee.label help=$fee.description data-amount=$fee.amount data-user-amount=$fee.user_amount data-account=$fee.id_account}
					{/foreach}
					</dl>
				</dd>
			{/foreach}

			<dt><strong>Montant de l'activité à payer</strong></dt>
			<dd><h3 class="money warning" id="target_amount" data-currency="{$config.monnaie}">--</h3></dd>
		</dl>
		<dl class="accounting">
			{input type="money" name="amount" label="Montant réglé par le membre"}
			{input type="list" target="acc/charts/accounts/selector.php?targets=%s"|args:$account_targets name="account" label="Compte de règlement" required=1}
		</dl>
		<dl>
			{input type="checkbox" name="paid" value="1" label="Marquer cette activité comme payée"}
			<dd class="help">En cas de règlement en plusieurs fois, il sera possible de cocher cette case lorsque le solde aura été réglé.</dd>
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

	if (elm.getAttribute('data-account')) {
		g.toggle('.accounting', true);
	}
	else {
		g.toggle('.accounting', false);
	}

	console.log(elm.getAttribute('data-account'));

	var a = $('#target_amount');

	if (amount) {
		a.innerHTML = g.formatMoney(amount) + ' ' + a.getAttribute('data-currency');
		a.setAttribute('data-amount', amount);
		$('#f_paid_amount').value = g.formatMoney(amount);
		$('#f_paid_1').checked = true;
	}
	else {
		a.innerHTML = 'prix libre';
		a.setAttribute('data-amount', 0);
		$('#f_paid_1').checked = true;
	}
}

$('input[name=id_service]').forEach((e) => {
	e.onchange = () => { selectService(e); };
});

$('input[name=id_fee]').forEach((e) => {
	e.onchange = () => { selectFee(e); };
});

$('#f_paid_amount').onkeyup = () => {
	var v = g.getMoneyAsInt($('#f_paid_amount').value);
	var expected = parseInt($('#target_amount').getAttribute('data-amount'), 10);

	if (v >= expected) {
		$('#f_paid_1').checked = true;
	}
	else {
		$('#f_paid_1').checked = false;
	}
}

var selected = document.querySelector('input[name="id_service"]:checked, input[name="id_service"]');
selected.checked = true;

selectService(selected);
</script>
{/literal}

{include file="admin/_foot.tpl"}