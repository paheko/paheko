{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>{$legend}</legend>
		<dl>
			{input name="label" type="text" required=1 label="Libellé" source=$service}
			{input name="description" type="textarea" label="Description" source=$service}

			{if $service && $service->exists()}
				{input type="checkbox" name="archived" value=1 label="Archiver cette activité" source=$service}
				<dd class="help">Si coché, les inscrits ne recevront plus de rappels, l'activité ne sera plus visible sur leur fiche, il ne sera plus possible d'y inscrire des membres.</dd>
			{/if}

			<dt><label for="f_periodicite_jours">Durée de validité</label> <b title="Champ obligatoire">(obligatoire)</b></dt>

			{if $service && $service->exists()}
			<dd class="help">Attention, une modification de la durée renseignée ici ne modifie pas la date d'expiration des activités déjà enregistrées.</dd>
			{/if}

			{input name="period" type="radio-btn" value="0" label="Pas de durée (activité ou cotisation ponctuelle)" default=$period help="Pour un événement, un concert, un cours ponctuel, etc."}
			{input name="period" type="radio-btn" value="1" label="En nombre de jours" default=$period help="Par exemple une cotisation valide un an à partir de la date d'inscription"}
			<dd class="period_1">
				<dl>
				{input name="duration" type="number" step="1" label="Nombre de jours" size="5" source=$service required=true default=365}
				</dl>
			</dd>
			{input name="period" type="radio-btn" value="2" label="Période définie (date à date)" default=$period help="Par exemple pour une cotisation qui serait valable pour l'année civile en cours, quelle que soit la date d'inscription."}
			<dd class="period_2">
				<dl class="periode_dates">
					{input type="date" name="start_date" label="Date de début" source=$service required=true}
					{input type="date" name="end_date" label="Date de fin" source=$service required=true}
				</dl>
			</dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript">
{literal}
(function () {
	var hide = [];
	if (!$('#f_period_1').checked)
		hide.push('.period_1');

	if (!$('#f_period_2').checked)
		hide.push('.period_2');

	g.toggle(hide, false);

	function togglePeriod()
	{
		g.toggle(['.period_1', '.period_2'], false);

		if (this.checked && this.value == 1)
			g.toggle('.period_1', true);
		else if (this.checked && this.value == 2)
			g.toggle('.period_2', true);
	}

	$('#f_period_0').onchange = togglePeriod;
	$('#f_period_1').onchange = togglePeriod;
	$('#f_period_2').onchange = togglePeriod;
})();
{/literal}
</script>