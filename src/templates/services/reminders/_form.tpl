{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>{$legend}</legend>
		<dl>
			{input type="select" name="id_service" options=$services_list label="Activité associée au rappel" required=1 source=$reminder}
			{input type="text" name="subject" required=1 source=$reminder label="Sujet du message envoyé"}

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
			{input type="textarea" name="body" required=1 source=$reminder label="Texte du message envoyé" cols="90" rows="15"}
			<dd class="help">
				Il est possible d'utiliser les mots-clés suivant dans le corps du mail, ils seront remplacés lors de l'envoi&nbsp;:
				<table class="list auto">
					<tr>
						<th>#IDENTITE</th>
						<td>Nom du membre</td>
					</tr>
					<tr>
						<th>#NB_JOURS</th>
						<td>Nombre de jours restants avant (ou après) expiration de l'inscription</td>
					</tr>
					<tr>
						<th>#DATE_RAPPEL</th>
						<td>Date d'envoi du rappel</td>
					</tr>
					<tr>
						<th>#DATE_EXPIRATION</th>
						<td>Date d'expiration de l'inscription</td>
					</tr>
					<tr>
						<th>#DELAI</th>
						<td>Nombre de jours défini dans le rappel</td>
					</tr>
					<tr>
						<th>#NOM_ASSO</th>
						<td>Nom de l'association</td>
					</tr>
					<tr>
						<th>#ADRESSE_ASSO</th>
						<td>Adresse de l'association</td>
					</tr>
					<tr>
						<th>#SITE_ASSO</th>
						<td>Adresse du site web de l'association</td>
					</tr>
				</table>
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
	$('#f_delay_before').onfocus = function () {
		$('#f_delay_type_1').checked = true;
	};
	$('#f_delay_after').onfocus = function () {
		$('#f_delay_type_2').checked = true;
	};
})();
{/literal}
</script>