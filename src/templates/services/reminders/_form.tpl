{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>{$legend}</legend>
		<dl>
			{input type="select" name="id_service" options=$services_list label="Activité associée au rappel" required=1 source=$reminder}
			{input type="text" name="subject" required=1 source=$reminder default=$default_subject label="Sujet du message envoyé"}

			<dt><label for="f_delay_type_0">Délai d'envoi</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			{input type="radio" name="delay_type" value=0 default=$delay_type label="Le jour de l'expiration de l'activité"}
			<dd>
				{input type="radio" name="delay_type" value=1 default=$delay_type}
				{input type="number" name="delay_before" min=1 max=999 default=$delay_before size=4}
				<label for="f_delay_type_1">jours <strong>avant</strong> expiration</label>
			</dd>
			<dd>
				{input type="radio" name="delay_type" value=2 default=$delay_type}
				{input type="number" name="delay_after" min=1 max=999 size=4 default=$delay_after}
				<label for="f_delay_type_2">jours <strong>après</strong> expiration</label>
			</dd>
			{input type="textarea" name="body" required=1 source=$reminder default=$default_body label="Texte du message envoyé" help="Pour inclure dans le contenu du mail le nom du membre, utilisez #IDENTITE, pour inclure le délai de l'envoi utilisez #NB_JOURS." cols="90" rows="15"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		<input type="submit" name="save" value="Enregistrer &rarr;" />
	</p>

</form>

<script type="text/javascript">
{literal}
(function () {
	$('#f_delay_before').onfocus = function () {
		$('#f_delay_type_1').checked = true;
	};
	$('#f_delay_after').onfocus = function () {
		$('#f_delay_type_2').checked = true;
	};
})();
{/literal}
</script>