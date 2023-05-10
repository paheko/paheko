{include file="_head.tpl" title="Édition : %s"|args:$page.title current="web" hide_title=true}

{form_errors}

{if $show_diff}
	<h3>Modifications entre votre version et la nouvelle version</h3>
	{diff old=$new_content new=$current_content}
{/if}

<form method="post" action="{$self_url}" class="web-edit" data-focus="#f_content">
	<fieldset class="header">
		<legend>Modification : {$page.title}</legend>
		<p>{input type="text" name="title" source=$page required=true class="full-width" placeholder="Titre" title="Modifier le titre" maxlength=200}</p>
		<div>
			<dl>{input type="list" name="parent" label="Catégorie" default=$parent target="!web/_selector.php?current=%s&parent=%s"|args:$page.path,$page.parent required=true}</dl>
			<dl>{input type="datetime" name="date" label="Date" required=true default=$page.published}</dl>
			<dl>{input type="select" name="format" required=true options=$formats source=$page label="Format"}</dl>
			<dl>{input type="checkbox" name="status" value=$page::STATUS_DRAFT label="Brouillon" source=$page}</dl>
		</ul>
	</fieldset>

	<fieldset class="editor">
		<div class="textEditor">
			{input type="textarea" name="content" cols="70" rows="20" default=$current_content data-attachments=1 data-savebtn=2 data-preview-url="!common/files/_preview.php?w=%s"|local_url|args:$page.path data-format="#f_format"}
		</div>
	</fieldset>

{*
	<fieldset class="content">
		{$page->render()|raw}
	</fieldset>

	<div class="block">
	</div>
*}

	<fieldset class="properties">
		{*
		<nav class="tabs">
			<ul>
				<li><a id="toggleVisualEditor">Éditeur visuel</a></li>
				<li class="current"><a id="toggleTextEditor">Éditeur texte</a></li>
			</ul>
		</nav>

		<p>
			<button name="toggleFullscreen">Plein écran</button>
		</p>
		*}

		<dl>
			{input type="text" label="Identifiant unique de la page" name="uri" default=$page.uri required=true help="Utilisé pour désigner l'adresse de la page sur le site. Ne peut comporter que des lettres, chiffres et tirets." pattern="[A-Za-z0-9_-]+" class="full-width" maxlength=150}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		<input type="hidden" name="editing_started" value="{$editing_started}" />
		{button type="submit" name="save" label="Enregistrer et fermer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}