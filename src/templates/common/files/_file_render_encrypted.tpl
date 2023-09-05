<noscript>
	<div class="error">
		Vous dever activer javascript pour pouvoir déchiffrer cette page.
	</div>
</noscript>
<script type="text/javascript" src="{$admin_url}static/scripts/web_encryption.js"></script>
<div id="web_encrypted_message">
	<p class="block alert">Cette page est chiffrée.
		<input type="button" onclick="return pleaseDecrypt();" value="Entrer le mot de passe" />
	</p>
</div>
<div class="web-content" style="display: none;" id="web_encrypted_content" data-url="{$www_url}">
	{$content}
</div>