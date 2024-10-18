{include file="_head.tpl" title="Déplacer des fichiers vers une écriture" current="docs"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Déplacer des fichiers vers une écriture</legend>
		<p class="help">{{%n fichier sélectionné.}{%n fichiers sélectionnés.} n=$count}</p>
		<dl>
			{input type="number" label="Numéro d'écriture" required=true name="id"}
		</dl>
		<p class="submit">
			{button shape="right" label="Déplacer" name="move" type="submit" class="main"}
		</p>
	</fieldset>
	{csrf_field key=$csrf_key}

	{foreach from=$check key="key" item="value"}
		<input type="hidden" name="check[]" value="{$value}" />
	{/foreach}

	<input type="hidden" name="action" value="{$_POST.action}" />

</form>

{include file="_foot.tpl"}
