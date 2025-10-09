{include file="_head.tpl" title="Désinscription" layout="public" hide_title=true}

{if $verify === true}
	<p class="block confirm">
		Votre adresse e-mail a bien été vérifiée, merci !
	</p>

	<p>
	{if $site_url}
		{linkbutton href=$site_url label="Retour au site" shape="left"}
	{else}
		{linkbutton href=$admin_url label="Connexion" shape="left"}
	{/if}
	</p>

{elseif $verify === false}
	<p class="block error">
		Erreur de vérification de votre adresse e-mail.
	</p>
{elseif $ok}
	<p class="block confirm">
		Vous avez été bien désinscrit.<br />
		Vous ne recevrez plus ces messages de notre part.
	</p>

	<p class="help">
		Vous pouvez vous réinscrire à tout moment en cliquant à nouveau sur le lien de désinscription présent à la fin de nos e-mails.<br />
		{linkbutton shape="edit" label="Modifier mes préférences d'envoi" href=$prefs_url}
	</p>

{elseif $prefs == 2}

	{form_errors}

	<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Mes préférences d'envoi</legend>

		<dl>
			{input type="checkbox" name="accepts_messages" source=$email value=1 label="Tous les messages" prefix_title="Je souhaite recevoir les types de messages suivants :" prefix_required=true}
			{input type="checkbox" name="accepts_reminders" source=$email value=1 label="Rappels de cotisation et d'activité"}
			{input type="checkbox" name="accepts_mailings" source=$email value=1 label="Messages collectifs (lettres d'information)"}
		</dl>

		<p class="submit">
			{csrf_field key="optout"}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>
	</fieldset>
	</form>
{elseif $resub_ok}
	<p class="block confirm">
		Un e-mail vous a été envoyé, merci de cliquer sur le lien dans le message reçu pour terminer.
	</p>
{elseif $prefs == 1}

	<p class="block alert">
		Votre adresse e-mail est déjà désinscrite. Pour demander à vous réinscrire, renseignez le formulaire ci-dessous.
	</p>

	{form_errors}

	<form method="post" action="{$self_url}">

		<fieldset>

			<dl>
				{input type="email" required=true name="email" label="Adresse e-mail"}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key="optout"}
			{button type="submit" name="resub" label="Modifier mes préférences" shape="right" class="main"}
		</p>
	</form>
{else}

	{form_errors}

	<form method="post" action="{$self_url}">

		<p class="alert block">
			En cliquant sur ce bouton vous confirmez ne plus vouloir recevoir
			{if $context === Emails::CONTEXT_REMINDER}
			les rappels de cotisation et d'activité.
			{elseif $context === Emails::CONTEXT_BULK}
			les messages collectifs (lettres d'information).
			{else}
			les messages personnels de notre part.
			{/if}
		</p>

		<p class="submit">
			{csrf_field key="optout"}
			{button type="submit" name="optout" label="Me désinscrire" shape="right" class="main"}
		</p>

	</form>
{/if}

{include file="_foot.tpl"}