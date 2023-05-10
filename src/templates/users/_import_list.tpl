{foreach from=$list item="user"}
	<?php $diff_only = $user->exists() && $user->isModified(); ?>
	<h3 class="ruler">{$user->name()}</h3>

	<dl class="describe">
		{foreach from=$user->asDetailsArray() key="key" item="value"}
		{if $diff_only && !$user->isModified($key)}{continue}{/if}
			<dt>{$csv->getColumnLabel($key)}</dt>
			<dd>
				{if $user->exists() && $user->isModified() && ($old_value = $user->getModifiedProperty($key))}
					<del>{display_dynamic_field key=$key value=$old_value}</del><br />
					<ins>{display_dynamic_field key=$key value=$value}</ins>
				{else}
					{display_dynamic_field key=$key value=$value}
				{/if}
			</dd>
		{/foreach}
	</dl>
{/foreach}
