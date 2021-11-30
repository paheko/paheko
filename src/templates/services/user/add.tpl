{include file="admin/_head.tpl" title="Inscrire à une activité" current="membres/services"}

{include file="services/_nav.tpl" current="save" fee=null service=null}

{form_errors}

{if !$user_id}
<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Inscrire un membre à une activité</legend>
		<dl>
			{input type="list" name="user" required=1 label="Sélectionner un membre" default=$selected_user target="membres/selector.php"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="next" label="Continuer" shape="right" class="main"}
	</p>
</form>
{else}

	{include file="services/user/_service_user_form.tpl" create=true}

{/if}

{include file="admin/_foot.tpl"}