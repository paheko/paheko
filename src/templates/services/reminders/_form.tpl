{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>{$legend}</legend>
		<dl>
			{input type="select" name="id_service" options=$services_list label="Activité associée au rappel" required=1 source=$reminder}

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

			{if !$reminder->exists()}
				<?php $yes_before = ($reminder->not_before_date ?? 1) === null; ?>
				{input type="radio-btn" name="yes_before" value=0 default=$yes_before label="Uniquement aux membres dont l'inscription n'a pas encore expiré" help="Seuls les inscriptions qui expirent à partir de demain seront concernées par ce rappel." prefix_title="Envoyer ce rappel…" prefix_required=true }
				{input type="radio-btn" name="yes_before" value=1 default=$yes_before label="À tous les membres" help="Même si leur inscription a expiré il y a longtemps, sauf s'ils ont déjà reçu un rappel pour cette activité.\nCela peut générer un grand nombre d'envois à des membres qui ne sont plus à jour depuis longtemps !"}
			{else}
				<dt><strong>Restriction d'envoi</strong></dt>
				{if $reminder.not_before_date}
					<dd><p class="alert block">Aucun rappel ne sera envoyé aux inscriptions expirant avant le {$reminder.not_before_date|date_short}</p></dd>
				{else}
					<dd>Aucune restriction. Tous les membres recevront ce rappel, selon le délai choisi.</dd>
				{/if}
			{/if}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Message envoyé</legend>
		<dl>
			{input type="text" name="subject" required=1 source=$reminder label="Sujet"}
			{input type="textarea" name="body" required=1 source=$reminder label="Texte" cols="90" rows="15"}
			<dd class="help">
				Il est possible d'utiliser les mots-clés suivant dans le corps du mail, ils seront remplacés lors de l'envoi&nbsp;:
				{literal}
				<table class="list auto">
					<tr>
						<th scope="row">{{$label}}</th>
						<td>Nom de l'activité concernée par le rappel</td>
					</tr>
					<tr>
						<th scope="row">{{$fee_label}}</th>
						<td>Nom du tarif utilisé lors de la dernière inscription du membre à cette activité</td>
					</tr>
					<tr>
						<th scope="row">{{$id_user}}</th>
						<td>ID du membre concerné par le rappel</td>
					</tr>
					<tr>
						<th scope="row">{{$identity}}</th>
						<td>Nom du membre</td>
					</tr>
					<tr>
						<th scope="row">{{$email}}</th>
						<td>Adresse e-mail utilisée pour l'envoi du rappel au membre</td>
					</tr>
					<tr>
						<th scope="row">{{$nb_days}}</th>
						<td>Nombre de jours restants avant (ou après) expiration de l'inscription</td>
					</tr>
					<tr>
						<th scope="row">{{$reminder_date}}</th>
						<td>Date d'envoi du rappel</td>
					</tr>
					<tr>
						<th scope="row">{{$expiry_date}}</th>
						<td>Date d'expiration de l'inscription</td>
					</tr>
					<tr>
						<th scope="row">{{$user_amount}}</th>
						<td>Montant dû par le membre pour se réinscrire à cette activité</td>
					</tr>
					<tr>
						<th scope="row">{{$delay}}</th>
						<td>Nombre de jours défini dans le rappel</td>
					</tr>
					<tr>
						<th scope="row">{{$config.org_name}}</th>
						<td>Nom de l'association</td>
					</tr>
					<tr>
						<th scope="row">{{$config.org_address}}</th>
						<td>Adresse postale de l'association</td>
					</tr>
					<tr>
						<th scope="row">{{$site_url}}</th>
						<td>Adresse du site web de l'association</td>
					</tr>
				</table>
				<p class="help">Note : il est aussi possible d'utiliser les champs de la fiche membre, par exemple <tt>{{$nom}}</tt> pour le nom du membre.</p>
				{/literal}
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