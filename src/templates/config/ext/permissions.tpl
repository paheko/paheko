{include file="_head.tpl" title="%s — Permissions"|args:$ext.label current="config"}

{include file="config/_menu.tpl" current="ext"}
{include file="./_nav.tpl" current="permissions" ext=$ext}

{if !$ext.ini.allow_user_restrict || !$ext.restrict_section || !$ext.restrict_level}
	<p class="block error">Cette extension ne permet pas de modifier les permissions d'accès.</p>
{else}
	<form method="post" action="">
		<fieldset>
			<legend>Qui peut accéder à cette extension ?</legend>
			<dl>
				{input type="radio" name="restrict" value="connect_1" default=$current_permission label="Tous les membres pouvant se connecter"}
				{input type="radio" name="restrict" value="config_9" default=$current_permission label="Seulement les membres ayant accès à la configuration"}
				<dt><strong>Seulement les membres ayant accès à la gestion des membres…</strong></dt>
				{input type="radio" name="restrict" value="users_1" default=$current_permission label="… en lecture"}
				{input type="radio" name="restrict" value="users_2" default=$current_permission label="… en lecture et écriture"}
				{input type="radio" name="restrict" value="users_9" default=$current_permission label="… en administration"}
				<dt><strong>Seulement les membres ayant accès à la comptabilité…</strong></dt>
				{input type="radio" name="restrict" value="accounting_1" default=$current_permission label="… en lecture"}
				{input type="radio" name="restrict" value="accounting_2" default=$current_permission label="… en lecture et écriture"}
				{input type="radio" name="restrict" value="accounting_9" default=$current_permission label="… en administration"}
				<dt><strong>Seulement les membres ayant accès aux documents…</strong></dt>
				{input type="radio" name="restrict" value="documents_1" default=$current_permission label="… en lecture"}
				{input type="radio" name="restrict" value="documents_2" default=$current_permission label="… en lecture et écriture"}
				{input type="radio" name="restrict" value="documents_9" default=$current_permission label="… en administration"}
				<dt><strong>Seulement les membres ayant accès à la gestion du site web…</strong></dt>
				{input type="radio" name="restrict" value="web_1" default=$current_permission label="… en lecture"}
				{input type="radio" name="restrict" value="web_2" default=$current_permission label="… en lecture et écriture"}
				{input type="radio" name="restrict" value="web_9" default=$current_permission label="… en administration"}
			</dl>
		</fieldset>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" class="main" label="Enregistrer" name="save" shape="right"}
		</p>
	</form>
{/if}

{include file="_foot.tpl"}