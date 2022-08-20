{include file="_head.tpl" title="Formulaires & modèles" current="config"}

{include file="config/_menu.tpl" current="forms"}

<table class="list">
	<tbody>
		{foreach from=$list item="form"}
		<tr>
			<td><h3>{$form.label}</h3>{$form.description|escape|nl2br}</td>
			<td class="actions">
				{if $form.enabled}
					{linkbutton label="Désactiver" shape="eye-off" href="?disable=%s"|args:$form.name}
				{else}
					{linkbutton label="Activer" shape="eye" href="?enable=%s"|args:$form.name}
				{/if}
				{*{linkbutton label="Modifier" href="edit.php?form=%s"|args:$form.name shape="edit" target="_dialog"}*}
				{if $form->hasConfig()}
					<br />{linkbutton label="Configurer" href=$form->url($form::CONFIG_TEMPLATE) shape="settings" target="_dialog"}
				{/if}
				{if $form->canDelete()}
					<br />
					{if $form->hasDist()}
						{linkbutton label="Remettre à zéro" href="delete.php?form=%s"|args:$form.name shape="reset" target="_dialog"}
					{else}
						{linkbutton label="Supprimer" href="delete.php?form=%s"|args:$form.name shape="delete" target="_dialog"}
					{/if}
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>

{include file="_foot.tpl"}