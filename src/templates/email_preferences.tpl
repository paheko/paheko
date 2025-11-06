{use Paheko\Email\Emails}
{include file="_head.tpl" title="Préférences de réception de messages" layout="public" hide_title=true}

{if isset($_GET['saved'])}
	<p class="block confirm">
		Vos préférences ont bien été enregistrées.
	</p>
{elseif isset($_GET['confirmation_sent'])}
	<p class="block alert">
		Un e-mail vous a été envoyé, merci de cliquer sur le lien dans le message reçu pour confirmer vos préférences.
	</p>
{else}
	{form_errors}

	{if $verified}
		<p class="block confirm">
			Votre adresse e-mail a bien été vérifiée, merci !
		</p>
	{elseif $optout_context}
		<p class="confirm block">
			Vous avez bien été désinscrit. Vous ne recevrez plus
			{if $optout_context === Emails::CONTEXT_REMINDER}
			les rappels de cotisation et d'activité.
			{elseif $optout_context === Emails::CONTEXT_BULK}
			les messages collectifs (lettres d'information).
			{else}
			les messages personnels de notre part.
			{/if}
		</p>
	{elseif $address_required}
		<p class="block alert">
			Merci de bien vouloir indiquer votre adresse e-mail pour confirmer que vous souhaitez vous ré-inscrire.<br/>
			<strong>Un message vous sera envoyé pour confirmer la réinscription.</strong>
		</p>
	{/if}

	<form method="post" action="{$form_url}">

		<fieldset>
			<legend>Préférences</legend>

			<dl>
				{if $address_required}
					{input type="email" required=true name="email" label="Adresse e-mail"}
				{/if}
				<dt><label for="f_accepts_messages_1">Je souhaite recevoir les types de messages suivants :</label></dt>
				{input type="checkbox" name="accepts_messages" source=$email value=1 label="Messages personnels"}
				{input type="checkbox" name="accepts_reminders" source=$email value=1 label="Rappels de cotisation et d'activité"}
				{input type="checkbox" name="accepts_mailings" source=$email value=1 label="Messages collectifs (lettres d'information)"}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>

	</form>
{/if}

{include file="_foot.tpl"}