{include file="_head.tpl" title="Chiffrement des e-mails" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

{if $pgp_fingerprint}
	<dd class="help">L'empreinte de votre clé publique est&nbsp;: <code>{$pgp_fingerprint}</code></dd>

	<h3>Désactiver le chiffrement des e-mails ?</h3>

	{include file="./_security_confirm_password.tpl"}
{else}
	<fieldset>
		<legend>Chiffrer les e-mails qui me sont envoyés avec PGP/GnuPG</legend>
		<p class="help">En inscrivant ici votre clé publique, tous les e-mails qui vous seront envoyés seront chiffrés (cryptés) avec cette clé&nbsp;: messages collectifs, messages envoyés par les membres, rappels de cotisation, procédure de récupération de mot de passe, etc.</p>
		<dl>
			{input name="pgp_key" source=$user label="Ma clé publique PGP" type="textarea" cols=90 rows=5 required=true}
		</dl>
		<p class="block alert">
			Attention&nbsp;: en inscrivant ici votre clé PGP, les emails de récupération de mot de passe perdu vous seront envoyés chiffrés
			et ne pourront donc être lus si vous n'avez pas le le mot de passe protégeant la clé privée correspondante.
		</p>
	</fieldset>

	{include file="./_security_confirm_password.tpl"}
{/if}

</form>

{include file="_foot.tpl"}