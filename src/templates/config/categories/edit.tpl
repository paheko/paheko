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
				- ne seront visibles que par les membres ayant le droit d'administration&nbsp;;<br />
				- ne recevront pas de messages collectifs&nbsp;;<br />
				- ne recevront pas de rappels de cotisation&nbsp;;<br />
				- leurs inscriptions aux activités seront cachées.<br />
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

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}