{include file="admin/_head.tpl" title="Nouveau compte" current="acc/charts"}

{include file="acc/charts/accounts/_nav.tpl" current="new"}

{form_errors}

{if !isset($account->type)}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Créer un nouveau compte</legend>
		<dl><label for="f_type">Type de compte</label></dl>
		{foreach from=$types_create item="t" key="v"}
			{input type="radio-btn" name="type" value=$v label=$t.label help=$t.help}
		{/foreach}
	</fieldset>
	<p class="submit">
		<input type="hidden" name="id" value="{$chart.id}" />
		{button type="submit" label="Continuer" shape="right" class="main"}
	</p>
</form>

{else}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Créer un nouveau compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" edit_disabled=false create=true}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_new"}
		{button type="submit" name="save" label="Créer" shape="right" class="main"}
	</p>

</form>

{/if}

{include file="admin/_foot.tpl"}