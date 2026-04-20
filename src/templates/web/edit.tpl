{include file="_head.tpl" title="Édition : %s"|args:$page.title current="web" hide_title=true}

{form_errors}

{if $show_diff}
	{diff old=$my_content new=$page->content old_label="Votre version" new_label="Version enregistrée"}
{elseif $restored_version}
	<p class="alert block">
		Attention, le texte a été restauré depuis une version précédente, vous risquez d'écraser des modifications.
	</p>
{/if}

<form method="post" action="{$self_url}" class="web-edit" data-focus="#f_content">
	<fieldset class="content">
		<legend>{if $page.type === $page::TYPE_CATEGORY}Modifier la catégorie{else}Modifier la page{/if}</legend>
		<p>{input type="text" name="title" source=$page required=true class="full-width" placeholder="Titre" aria-label="Titre" title="Titre" maxlength=200}</p>
		<div class="textEditor">
			{input type="textarea" name="content" cols="70" rows="20" source=$page data-attachments=1 data-savebtn=2 data-preview-url="!common/files/_preview.php?w=%d"|local_url|args:$page.id data-format="#f_format" data-id=$page.id class="full-width" required=false}
		</div>
	</fieldset>

	<div>
		<fieldset class="options">
			<legend>Options</legend>
			<dl>
				{input type="list" name="id_parent" label="Catégorie" default=$parent target="!web/_selector.php?id_parent=%d&id_page=%d"|args:$page.id_parent:$page.id required=true}
				{input type="datetime" name="date" label="Date" required=true default=$page.published}
				{input type="select" name="status" label="Statut" source=$page options=$page::STATUS_LIST required=true}
				{if $page.type === $page::TYPE_CATEGORY}
					{input type="select" name="list_order" label="Ordre des pages" source=$page options=$page::LIST_ORDERS required=true help="Indique l'ordre d'affichage des pages de cette catégorie sur le site public."}
				{/if}
				{input type="select" name="format" required=true options=$formats source=$page label="Format"}
				{input type="text" label="Identifiant unique de la page" name="uri" default=$page.uri required=true help="Utilisé pour désigner l'adresse de la page sur le site. Ne peut comporter que des lettres, chiffres et tirets." pattern="[A-Za-z0-9_\-]+" class="full-width" maxlength=150}
			</dl>
		</fieldset>

		<p class="submit">
			{csrf_field key=$csrf_key}
			<input type="hidden" name="editing_started" value="{$editing_started}" />
			{button type="submit" name="save" label="Enregistrer et fermer" shape="right" class="main"}
		</p>
	</div>

</form>

{include file="_foot.tpl"}