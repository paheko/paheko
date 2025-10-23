{include file="_head.tpl" title="Vérification" layout="public" hide_title=true}

{if $verify === true}
	<p class="block confirm">
		Votre adresse e-mail a bien été vérifiée, merci !
	</p>

{else}
	<p class="block error">
		Erreur de vérification de votre adresse e-mail.
	</p>

{/if}

{include file="_foot.tpl"}