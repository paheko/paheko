{use Paheko\Email\Emails}
{include file="_head.tpl" title="Préférences de réception de messages" layout="public" hide_title=true}

{if isset($_GET['saved'])}
	<p class="block confirm">
		Vos préférences ont bien été enregistrées.
	</p>

	<p class="actions">
		{linkbutton shape="settings" label="Modifier mes préférences de réception" href=$form_url}
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
		<p class="alert block">
			Merci de bien vouloir valider le formulaire pour confirmer
			que vous ne souhaitez plus recevoir
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

	<form method="post" action="{$form_url}" class="email-preferences">

	{if !$optout_context}
		<fieldset>
			<legend>Préférences de réception</legend>

			<dl>
			{if $address_required}
				{input type="email" required=true name="email" label="Adresse e-mail"}
			{/if}
				{input type="radio" name="accepts_mailings" value=1 source=$email label="Recevoir" prefix_title="Messages collectifs" prefix_required=true prefix_help="Indiquez si vous souhaitez recevoir les lettres d'information, appels à bénévoles, convocations à l'assemblée générale, etc."}
				{input type="radio" name="accepts_mailings" value=0 source=$email label="Ne pas recevoir"}
				{input type="radio" name="accepts_messages" value=1 source=$email label="Recevoir" prefix_title="Messages personnels" prefix_required=true prefix_help="Indiquez si vous souhaitez recevoir les e-mails envoyés par un autre membre ou un⋅e administrateur⋅trice."}
				{input type="radio" name="accepts_messages" value=0 source=$email label="Ne pas recevoir"}
				{input type="radio" name="accepts_reminders" value=1 source=$email label="Recevoir" prefix_title="Notifications" prefix_required=true prefix_help="Indiquez si vous souhaitez recevoir les rappels de cotisation ou d'activité, confirmation d'inscription à un événement, etc."}
				{input type="radio" name="accepts_reminders" value=0 source=$email label="Ne pas recevoir"}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>

	{else}

		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="save" label="Me désinscrire" shape="right" class="main"}
		</p>

	{/if}

	</form>
{/if}

{include file="_foot.tpl"}