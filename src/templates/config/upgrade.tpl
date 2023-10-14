{include file="_head.tpl" title="Mise à jour" current="config"}

{include file="config/_menu.tpl" current="index"}

{form_errors}

<form method="post" action="{$self_url}">

{if !count($releases)}
	<p class="block alert">Aucune mise à jour n'est disponible.</p>
{elseif $downloaded && $verified === false}
	<p class="error block">Le fichier d'installation est corrompu.</p>
{elseif $downloaded}
	<fieldset>
		<legend>Mise à jour vers {$version}</legend>
		{if $verified === true}
		<p class="help">
			Le fichier d'installation a été correctement vérifié.
		</p>
		{else}
		<p class="block alert">
			L'intégrité du fichier d'installation n'a pas pu être vérifié automatiquement.
			{if !$can_verify}
			<br />(Cela est probablement dû au fait que votre installation ne dispose pas du module <em>GnuPG</em>.)
			{/if}
		</p>
		{/if}
		<details>
			<summary><h3>{$diff.delete|count} fichiers seront supprimés</h3></summary>
			<dl>
			{foreach from=$diff.delete key="file" item="path"}
				<dd>{$file}</dd>
			{/foreach}
			</dl>
		</details>
		<details>
			<summary><h3>{$diff.create|count} fichiers seront rajoutés</h3></summary>
			<dl>
			{foreach from=$diff.create key="file" item="path"}
				<dd>{$file}</dd>
			{/foreach}
			</dl>
		</details>
		<details>
			<summary><h3>{$diff.update|count} fichiers seront modifiés</h3></summary>
			<p class="alert block">
				Si vous aviez bidouillé ces fichiers, les modifications seront écrasées.
			</p>
			<dl>
			{foreach from=$diff.update key="file" item="path"}
				<dd>{$file}</dd>
			{/foreach}
			</dl>
		</details>
		<dl class="block error">
			{input type="checkbox" name="upgrade" value=$version label="Je confirme vouloir procéder à la mise à jour" help="Cette action peut casser votre installation !"}
		</dl>
	</fieldset>

	<p class="alert block">N'oubliez pas d'aller {link href="%swiki/?name=Changelog"|args:$website target="_blank" label="lire le journal des changements"} avant d'effectuer la mise à jour&nbsp;!</p>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="next" label="Effectuer la mise à jour" shape="right" class="main"}
	</p>
{else}
	<fieldset>
		<legend>Mise à jour</legend>
		<dl>
		{foreach from=$releases key="version" item="release"}
			{input type="radio" name="download" value=$version label=$version}
			{if $version == $latest}
			<dd class="help">
				Dernière version stable, conseillée.
			</dd>
			{/if}
		{/foreach}
		</dl>
	</fieldset>

	<p class="alert block">N'oubliez pas d'aller {link href="%swiki/?name=Changelog"|args:$website target="_blank" label="lire le journal des changements"} avant d'effectuer la mise à jour&nbsp;!</p>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="next" label="Télécharger" shape="right" class="main"}
	</p>
{/if}

</form>

{include file="_foot.tpl"}