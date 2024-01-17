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
			<?php
			$number_field = \Paheko\Users\DynamicFields::getNumberField();
			?>
			{foreach from=$user->asDetailsArray() key="key" item="value"}
				{if $key === $number_field}
					{* Don't show number field when creating user*}
					{continue}
				{/if}
				<dt>{$csv->getColumnLabel($key)}</dt>
				<dd>
					{user_field name=$key value=$value}
				</dd>
			{/foreach}
		{/if}
	</dl>
{/foreach}
