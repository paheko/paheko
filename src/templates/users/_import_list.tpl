{foreach from=$list item="user"}
	<?php $diff_only = $user->exists() && $user->isModified(); ?>
	<h3 class="ruler">{$user->name()}</h3>

	<dl class="describe">
		{foreach from=$user->asDetailsArray() key="key" item="value"}
		{if $diff_only && !$user->isModified($key)}{continue}{/if}
			<dt>{$csv->getColumnLabel($key)}</dt>
			<dd>
				{if $user->exists() && $user->isModified() && ($old_value = $user->getModifiedProperty($key))}
					<del>{user_field name=$name value=$old_value}</del><br />
					<ins>{user_field name=$name value=$value}</ins>
				{else}
					{user_field key=$key value=$value}
				{/if}
			</dd>
		{/foreach}
	</dl>
{/foreach}
