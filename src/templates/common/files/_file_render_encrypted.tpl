<noscript>
	<div class="error">
		Vous dever activer javascript pour pouvoir déchiffrer cette page.
	</div>
</noscript>
<script type="text/javascript" src="{$admin_url}static/scripts/wiki-encryption.js"></script>
<div id="wikiEncryptedMessage">
	<p class="block alert">Cette page est chiffrée.
		<input type="button" onclick="return wikiDecrypt(false);" value="Entrer le mot de passe" />
	</p>
</div>
<div class="wikiContent" style="display: none;" id="wikiEncryptedContent">
	{$page.contenu.contenu}
</div>