{include file="_head.tpl" title=$title}

{if $sent}
	<p class="block confirm">
		{if $new}
			Un e-mail vous a été envoyé, cliquez sur le lien dans cet e-mail pour choisir votre mot de passe.
		{else}
			Un e-mail vous a été envoyé, cliquez sur le lien dans cet e-mail pour modifier votre mot de passe.
		{/if}
	</p>
	<p class="help">
		Si le message n'apparaît pas dans les prochaines minutes, vérifiez le dossier Spam ou Indésirables.
	</p>

{else}

	{form_errors}

	<form method="post" action="{$self_url}" data-focus="1">

		<fieldset>
			<legend>{if $new}Envoyer un e-mail pour choisir son mot de passe{else}Envoyer un e-mail pour modifier son mot de passe{/if}</legend>
			<p class="help">
				Inscrivez ici votre identifiant.<br/>
				{if $new}
					Vous recevrez un e-mail à l'adresse renseignée dans votre fiche membre, avec un lien vous permettant de créer votre mot de passe.
				{else}
					Nous vous enverrons un e-mail à l'adresse renseignée dans votre fiche membre, avec un lien vous permettant de modifier votre mot de passe.
				{/if}
			</p>
			<dl>
				{input type=$id_field.type label=$id_field.label required=true name="id"}
			</dl>
		</fieldset>

		{if $captcha}
		<fieldset>
			<legend>Vérification de sécurité</legend>
			<input type="hidden" name="c_hash" value="{$captcha.hash}" />
			<dl>
				<dt><label for="f_c_answer">Merci de recopier en chiffres (par exemple <em>1234</em>) le nombre suivant :<b>(obligatoire)</b></label></dt>
				<dd><tt>{$captcha.spellout}</tt></dd>
				<dd>{input name="c_answer" type="text" maxlength=4 label=null required=true}</dd>
				<dd class="help">Cette vérification est demandée après plusieurs tentatives de connexion infructueuses.</dd>
			</dl>
		</fieldset>
		{/if}


		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="recover" label="Envoyer" shape="right" class="main"}
		</p>

	</form>
{/if}

{include file="_foot.tpl"}