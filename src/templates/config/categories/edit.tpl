{include file="_head.tpl" title="Modifier une catégorie de membre" current="config"}

{include file="config/_menu.tpl" current="categories"}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Informations générales</legend>
		<dl>
			{input type="text" name="name" label="Nom" required=true source=$cat}
			<dt>Configuration</dt>
			{input type="checkbox" name="hidden" label="Catégorie cachée" source=$cat value=1}
			<dd class="help">
				Si coché, les membres de cette catégorie&nbsp;:<br />
				- ne seront plus visibles par défaut&nbsp;;<br />
				- ne pourront plus être inscrits à des activités&nbsp;;<br />
				- ne pourront plus être associés à des écritures comptables&nbsp;;<br />
				- ne recevront pas de messages collectifs&nbsp;;<br />
				- ne recevront pas de rappels de cotisation.<br />
				<em>Utile par exemple pour archiver les membres qui n'ont pas renouvelé leur cotisation, avant suppression.</em>
			</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Droits</legend>
		<dl class="permissions">
		{foreach from=$permissions key="type" item="perm"}
			<dt><label for="f_perm_{$type}_0">{$perm.label}</label></dt>
			{if $perm.disabled}
				<dd class="alert block">
					En tant qu'administrateur, vous ne pouvez pas désactiver ce droit pour votre propre catégorie.<br />
					Ceci afin d'empêcher que vous ne puissiez plus vous connecter.
				</dd>
			{/if}
			{foreach from=$perm.options key="level" item="label"}
			<dd>
				<input type="radio" name="perm_{$type}" value="{$level}" id="f_perm_{$type}_{$level}" {if $cat->{'perm_' . $type} == $level}checked="checked"{/if} {if $perm.disabled}disabled="disabled"{/if} />
				<label for="f_perm_{$type}_{$level}"><b class="access_{$level}">{$perm.shape}</b> {$label}</label>
			</dd>
			{/foreach}
		{/foreach}
		</dl>
	</fieldset>

	{*TODO: advanced category security options
	<fieldset>
		<legend>Sécurité</legend>
		<dl>
			{input type="checkbox" name="allow_passwordless_login" value=1 source=$cat label="Permettre la connexion sans mot de passe"}
			<dd class="help">Si cette case est cochée, les membres pourront se connecter sans utiliser de mot de passe, simplement via un lien à usage unique qui leur sera envoyé par e-mail.</dd>
			{input type="checkbox" name="force_otp" value=1 source=$cat label="Obliger à utiliser la double authentification"}
			<dd class="help">Si cette case est cochée, les membres de cette catégorie seront obligés de configurer un second facteur de sécurité (code unique généré sur téléphone avec TOTP) lors de leur première connexion pour pouvoir se connecter. Conseillé pour les administrateurs.</dd>
			{if $has_encryption}
				{input type="checkbox" name="force_pgp" value=1 source=$cat label="Obliger à chiffrer les e-mails avec PGP"}
				<dd class="help">Si coché, un membre ne pourra se connecter que s'il indique une clé PGP publique valide, permettant de chiffrer tous les e-mails qui lui sont envoyés. Cette fonctionnalité permet d'empêcher la connexion d'un attaquant si la boîte mail du membre est piratée. Cela permet également de s'assurer de la confidentialité des e-mails envoyés aux membres.</dd>
			{/if}
		</dl>
	</fieldset>
	*}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}