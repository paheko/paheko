{include file="_head.tpl" title="Chiffrement des e-mails" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

{if $user.pgp_key}
	{include file="./_security_confirm_password.tpl" warning="Désactiver le chiffrement des e-mails ?"}
{else}
	<fieldset>
		<legend>Chiffrer les e-mails qui me sont envoyés avec PGP/GnuPG</legend>
		<p class="help">En inscrivant ici votre clé publique, tous les e-mails qui vous seront envoyés seront chiffrés (cryptés) avec cette clé&nbsp;: messages collectifs, messages envoyés par les membres, rappels de cotisation, procédure de récupération de mot de passe, etc.</p>
		<p class="help">Cela permet de conserver la confidentialité des messages envoyés, mais aussi d'empêcher d'accéder à ce compte si votre boîte mail se fait pirater.</p>
		<dl>
			{input name="pgp_key" source=$user label="Ma clé publique PGP" type="textarea" cols=90 rows=5 required=true}
		</dl>
		<p class="block alert">
			Attention&nbsp;: les emails de récupération de mot de passe perdu vous seront envoyés chiffrés avec votre clé PGP. Ces messages ne  pourront donc pas être lus si vous perdez le mot de passe protégeant cette clé.
		</p>
	</fieldset>

	{include file="./_security_confirm_password.tpl"}
{/if}

</form>

{include file="_foot.tpl"}