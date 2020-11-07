{include file="admin/_head.tpl" title="Enregistrer une activité" current="membres/services" js=1}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Enregistrer une activité</legend>

{if !$user_id}
		<dl>
			{input type="list" name="user" required=1 label="Sélectionner un membre" default=$selected_user target="membres/selector.php"}
		</dl>
{else}
		<dl>
			<dt>Membre sélectionné</dt>
			<dd><h3>{$user_name}</h3><input type="hidden" name="id_user" value="{$user_id}" /></dd>
			<dt><label for="f_service_ID">Activité</label> <b>(obligatoire)</b></dt>

		{foreach from=$grouped_services item="service"}
			<dd>
				<label class="radio-btn">
					{input type="radio" name="id_service" value=$service.id}
					<div>
						<h3>{$service.label}</h3>
						<p>
							{if $service.duration}
								{$service.duration} jours
							{elseif $service.start_date}
								du {$service.start_date|date_short} au {$service.end_date|date_short}
							{else}
								ponctuelle
							{/if}
						</p>
						{if $service.description}
						<p class="help">
							{$service.description|escape|nl2br}
						</p>
						{/if}
					</div>
				</label>
			</dd>
		{/foreach}
		</dl>

		{foreach from=$grouped_services item="service"}
		<dl data-service="s{$service.id}">
			<dt><label for="f_fee">Tarif</label> <b>(obligatoire)</b></dt>
			{foreach from=$service.fees key="service_id" item="fee"}
			<dd>
				<label class="radio-btn">
					{input type="radio" name="id_fee" value=$fee.id data-user-amount=$fee.user_amount data-account=$fee.id_account}
					<div>
						<h3>{$fee.label}</h3>
						<p>
							{if !$fee.user_amount}
								prix libre ou gratuit
							{elseif $fee.user_amount && $fee.formula}
								<strong>{$fee.user_amount|raw|money_currency}</strong> (montant calculé)
							{elseif $fee.user_amount}
								<strong>{$fee.user_amount|raw|money_currency}</strong>
							{/if}
						</p>
						{if $fee.description}
						<p class="help">
							{$fee.description|escape|nl2br}
						</p>
						{/if}
					</div>
				</label>
			</dd>
			{/foreach}
		</dl>
		{/foreach}

		<dl>
			{input type="checkbox" name="paid" value="1" default="1" label="Marquer cette activité comme payée"}
			<dd class="help">Décocher cette case pour pouvoir suivre les règlements de personnes qui payent en plusieurs fois. Il sera possible de cocher cette case lorsque le solde aura été réglé.</dd>
		</dl>
	</fieldset>

	<fieldset class="accounting">
		<legend>Enregistrement en comptabilité</legend>

		<dl>
			{input type="money" name="amount" label="Montant réglé par le membre" required=1 help="En cas de règlement en plusieurs fois il sera possible d'ajouter des règlements via la page de suivi des activités de ce membre."}
			{input type="list" target="acc/charts/accounts/selector.php?targets=%s"|args:$account_targets name="account" label="Compte de règlement" required=1}
			{input type="text" name="payment_reference" label="Référence de paiement" help="Numéro de chèque, numéro de transaction CB, etc."}
		</dl>
{/if}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{if $user_id}
			<input type="submit" name="save" value="Enregistrer &rarr;" />
		{else}
			<input type="submit" name="next" value="Continuer &rarr;" />
		{/if}
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
	var amount = parseInt(elm.getAttribute('data-user-amount'), 10);

	// Toggle accounting part of the form
	var accounting = elm.getAttribute('data-account') ? true : false;
	g.toggle('.accounting', accounting);
	$('#f_amount').required = accounting;

	// Fill the amount paid by the user
	if (amount) {
		$('#f_amount').value = g.formatMoney(amount);
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