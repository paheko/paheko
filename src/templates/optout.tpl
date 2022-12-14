{include file="_head.tpl" title="Désinscription" layout="public"}

{if $verify === true}
	<p class="block confirm">
		Votre adresse e-mail a bien été vérifiée, merci !
	</p>

	<p>
	{if $config.site_asso || !$config.site_disabled}
		<?php $url = $config->site_asso ?? $www_url; ?>
		{linkbutton href=$url label="Retour au site" shape="left"}
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
		Vous avez été bien désinscrit, vous ne recevrez plus aucun message de notre part.
	</p>

	<p class="help">
		Vous pouvez vous réinscrire à tout moment en cliquant à nouveau sur le lien de désinscription présent à la fin de nos e-mails.<br />
		{linkbutton href="?un=%s"|args:$code label="Me réinscrire" shape="reload"}
	</p>
{elseif $resub_ok}
	<p class="block confirm">
		Un e-mail vous a été envoyé, merci de cliquer sur le lien dans le message reçu pour confirmer.
	</p>
{elseif $email.optout}

	<p class="block alert">
		Votre adresse e-mail est déjà désinscrite. Pour demander à vous réinscrire, renseignez le formulaire ci-dessous.
	</p>

	{form_errors}

	<form method="post" action="{$self_url}">

		<fieldset>
			<dl>
				{input type="email" required=true name="email" label="Adresse e-mail"}
				{input type="checkbox" name="confirm_resub" value="1" required=true label="Oui, je veux à nouveau recevoir les messages de « %s »"|args:$config.org_name}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key="optout"}
			{button type="submit" name="resub" label="Réinscrire mon adresse e-mail" shape="right" class="main"}
		</p>
	</form>
{else}

	{form_errors}

	<form method="post" action="{$self_url}">

		<p class="help">
			En cliquant sur ce bouton vous confirmez ne plus vouloir recevoir de messages de notre part.
		</p>

		<p class="submit">
			{csrf_field key="optout"}
			{button type="submit" name="optout" label="Me désinscrire" shape="right" class="main"}
		</p>

	</form>
{/if}

{include file="_foot.tpl"}