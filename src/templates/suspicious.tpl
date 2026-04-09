{include file="_head.tpl" title="Vérification de votre connexion" current=null layout="public" hide_title=true}

<div class="alert block">
	<h3>Votre adresse IP a été identifiée comme suspecte.</h3>
	<p>Merci d'attendre quelques secondes…</p>
	<noscript>
		<p>Ce formulaire ne fonctionne qu'avec Javascript, désolé.</p>
	</noscript>
</form>

<script type="text/javascript">
{literal}
window.setTimeout(() => {
	fetch(location.href).then(() => {
		window.setTimeout(() => location.reload(), 1000);
	});
}, 1000);
{/literal}
</script>

{include file="_foot.tpl"}