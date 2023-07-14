{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>{$legend}</legend>
		<h3 class="warning">
			{$warning}
		</h3>
		{if isset($alert)}
		<p class="block alert">
			{$alert}
		</p>
		{/if}
		{if isset($info)}
		<p class="help">
			{$info}
		</p>
		{/if}
		{if isset($confirm)}
		<p>
			{input type="checkbox" name="confirm_delete" value=1 label=$confirm}
		</p>
		{/if}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{if !isset($shape)}
			{assign var="shape" value="delete"}
		{/if}
		{button type="submit" name="delete" label="Supprimer" shape=$shape class="main"}
		{if isset($extra)}
			{foreach from=$extra key="key" item="value"}
				{if is_array($value)}
					{foreach from=$value key="subkey" item="subvalue"}
						<input type="hidden" name="{$key}[{$subkey}]" value="{$subvalue}" />
					{/foreach}
				{else}
					<input type="hidden" name="{$key}" value="{$value}" />
				{/if}
			{/foreach}
		{/if}
	</p>

</form>