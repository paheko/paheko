<?php
assert(isset($legend));
assert(isset($csrf_key));
assert(isset($submit_label));
$targets = Entities\Accounting\Account::TYPE_REVENUE;
?>

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>{$legend}</legend>
		<dl>
			{input name="label" type="text" required=1 label="Libellé" source=$fee}
			{input name="description" type="textarea" label="Description" source=$fee}

			<dt><label for="f_amount_type">Montant de la cotisation</label></dt>
			{input name="amount_type" type="radio" value="0" label="Gratuite ou prix libre" default=$amount_type}
			{input name="amount_type" type="radio" value="1" label="Montant fixe ou prix libre conseillé" default=$amount_type}
			<dd class="amount_type_1">
				<dl>
					{input name="amount" type="money" label="Montant" source=$fee}
				</dl>
			</dd>
			{input name="amount_type" type="radio" value="2" label="Montant variable" default=$amount_type}
			<dd class="amount_type_2">
				<dl>
					{input name="formula" type="textarea" label="Formule de calcul" source=$fee}
					<dd class="help">
						<a href="https://fossil.kd2.org/garradin/wiki?name=Formule_calcul_activit%C3%A9">Aide sur les formules de calcul</a>
					</dd>
				</dl>
			</dd>

			{input type="list" target="acc/charts/accounts/selector.php?targets=%s&chart_choice=1"|args:$targets name="account" label="Enregistrer les règlements dans ce compte du plan comptable" help="Si aucun compte n'est sélectionné, les règlements ne seront pas enregistrés en comptabilité" default=$account}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		<input type="submit" name="save" value="{$submit_label} &rarr;" />
	</p>

</form>

<script type="text/javascript">
{literal}
(function () {
	var hide = [];
	if (!$('#f_amount_type_1').checked)
		hide.push('.amount_type_1');

	if (!$('#f_amount_type_2').checked)
		hide.push('.amount_type_2');

	g.toggle(hide, false);

	function togglePeriod()
	{
		g.toggle(['.amount_type_1', '.amount_type_2'], false);

		if (this.checked && this.value == 1)
			g.toggle('.amount_type_1', true);
		else if (this.checked && this.value == 2)
			g.toggle('.amount_type_2', true);
	}

	$('#f_amount_type_0').onchange = togglePeriod;
	$('#f_amount_type_1').onchange = togglePeriod;
	$('#f_amount_type_2').onchange = togglePeriod;
})();
{/literal}
</script>