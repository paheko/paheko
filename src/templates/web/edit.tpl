{include file="admin/_head.tpl" title="Édition : %s"|args:$page.title current="web"}

{form_errors}

<form method="post" action="{$self_url}" class="web-edit">
	<fieldset>
		<legend>Édition</legend>
		<dl>
			{input type="text" label="Titre" required=true name="title" source=$page}
			{input type="text" label="Adresse unique" required=true name="uri" source=$page}
			{input type="checkbox" label="Brouillon" name="status" value=$page::STATUS_DRAFT source=$page help="Si coché, ne sera pas visible sur le site public"}
		</dl>
	</fieldset>
</form>


{include file="admin/_foot.tpl"}