{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing"}

{form_errors}

<form method="post" action="">
	<fieldset>
		<legend>Envoyer un message collectif</legend>
		<h3>Envoyer le message "{$mailing.subject}" à {$mailing->countRecipients()} destinataires ?</h3>
		{if $is_similar}
		<div class="block alert">
			<h3>Vous avez déjà envoyé un message collectif à la plupart de ces destinataires récemment&nbsp;!</h3>
			<p>Il est recommandé de ne pas envoyer plus de deux ou trois messages collectifs aux mêmes destinataires chaque mois.</p>
			<p>Une bonne pratique est de regrouper les informations au sein d'une lettre d'information mensuelle.</p>
			<p><strong>Si vous envoyez trop souvent des messages, vos envois risquent de finir en spam ou d'être définitivement bloqués&nbsp;!</strong></p>
			<dl>
				{input type="checkbox" name="confirm_send" label="Je comprends les risques, mais pour cette fois-ci j'ai besoin d'envoyer ce message" value=1}
			</dl>
		</div>
		{/if}
		{csrf_field key=$csrf_key}
		<p class="submit">{button class="main" type="submit" name="send" label="Envoyer" shape="right"}</p>
	</fieldset>
</form>

{include file="_foot.tpl"}