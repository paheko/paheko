{foreach from=$list item="user"}
	<?php $diff_only = $user->exists() && $user->isModified(); ?>
	<h3 class="ruler">{$user->name()}</h3>

	<dl class="describe">
		{if $user->exists() && $user->isModified()}
			{foreach from=$user->diff() key="key" item="diff"}
				<dt>{$csv->getColumnLabel($key)}</dt>
				<dd>
					<del>{user_field name=$key value=$diff[0]}</del>
					<ins>{user_field name=$key value=$diff[1]}</ins>
				</dd>
			{/foreach}
		{else}
			{foreach from=$user->asDetailsArray() key="key" item="value"}
				<dt>{$csv->getColumnLabel($key)}</dt>
				<dd>
					{user_field name=$key value=$value}
				</dd>
			{/foreach}
		{/if}
	</dl>
{/foreach}
