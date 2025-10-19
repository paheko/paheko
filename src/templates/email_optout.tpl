{use Paheko\Email\Emails}
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