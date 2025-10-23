{use Paheko\Email\Emails}
{include file="_head.tpl" title="Préférences d'envoi" layout="public" hide_title=true}

{if isset($_GET['saved'])}
	<p class="block confirm">
		Vos préférences ont bien été enregistrées.
	</p>
{elseif isset($_GET['sent'])}
	<p class="block alert">
		Un e-mail vous a été envoyé, merci de cliquer sur le lien dans le message reçu pour confirmer vos préférences.
	</p>
{else}
	{form_errors}

	{if $optout_context}
		<p class="alert block">
			Validez ce formulaire pour confirmer que vous ne souhaitez plus recevoir
			{if $optout_context === Emails::CONTEXT_REMINDER}
			les rappels de cotisation et d'activité.
			{elseif $optout_context === Emails::CONTEXT_BULK}
			les messages collectifs (lettres d'information).
			{else}
			les messages personnels de notre part.
			{/if}
		</p>
	{/if}

	<form method="post" action="{$self_url}">

		<fieldset>
			<legend>Préférences d'envoi</legend>

			<dl>
			{if !$optout_context}
				{input type="email" required=true name="email" label="Mon adresse e-mail"}
			{/if}
				{input type="checkbox" name="accepts_messages" source=$email value=1 label="Messages personnels" prefix_title="Je souhaite recevoir les types de messages suivants :" prefix_required=true}
				{input type="checkbox" name="accepts_reminders" source=$email value=1 label="Rappels de cotisation et d'activité"}
				{input type="checkbox" name="accepts_mailings" source=$email value=1 label="Messages collectifs (lettres d'information)"}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="validate" label="Enregistrer" shape="right" class="main"}
		</p>
		{if !$optout_context}
		<p class="help">Vous recevrez un message par e-mail avec un lien à cliquer, vous permettant de confirmer vos préférences d'envoi.</p>
		{/if}
	</form>
{/if}

{include file="_foot.tpl"}