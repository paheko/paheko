<?php
use Garradin\Users\DynamicFields as DF;
assert(isset($user, $show_message_button));

$user_files_path = $user->attachementsDirectory();

$id_fields = DF::getNameFields();
$email_button = 0;
$fields = DF::getInstance()->all();
?>

<dl class="describe">
	{foreach from=$fields key="key" item="field"}
	<?php
	// Skip private fields from "my info" page
	if ($mode == 'user' && !$field->read_access) {
		continue;
	}

	// Skip files from export
	if ($mode == 'export' && $field->type == 'file') {
		continue;
	}

	$value = $user->$key ?? null;
	?>
	<dt>{$field.label}</dt>
	<dd>
		{if $field.type == 'checkbox'}
			{if $value}Oui{else}Non{/if}
		{elseif $field.type == 'file'}
			<?php
			$edit = ($field->write_access || $mode == 'edit');
			?>
			{include file="common/files/_context_list.tpl" path="%s/%s"|args:$user_files_path,$key}
		{elseif empty($value)}
			<em>(Non renseigné)</em>
		{elseif in_array($key, $id_fields)}
			<strong>{$value}</strong>
		{elseif $field.type == 'email'}
			<a href="mailto:{$value|escape:'url'}">{$value}</a>
			{if $show_message_button && !$email_button++}
				{linkbutton href="!membres/message.php?id=%d"|args:$data.id label="Envoyer un message" shape="mail"}
			{/if}
		{elseif $field.type == 'multiple'}
			<ul>
			{foreach from=$field.options key="b" item="name"}
				{if $value & (0x01 << $b)}
					<li>{$name}</li>
				{/if}
			{/foreach}
			</ul>
		{else}
			{display_dynamic_field field=$field value=$value}
		{/if}
	</dd>
	{/foreach}
</dl>
