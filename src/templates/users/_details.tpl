<?php
use Paheko\Users\DynamicFields as DF;
use Paheko\Users\Session;
assert(isset($user, $show_message_button, $context));

$user_files_path = $user->attachmentsDirectory();

$id_fields = DF::getNameFields();
$email_button = 0;
$fields = DF::getInstance()->all();
?>

<dl class="describe">
	{foreach from=$fields key="key" item="field"}
	<?php
	// Skip private fields from "my info" page
	if ($context === 'user' && Session::ACCESS_NONE === $field->user_access_level) {
		continue;
	}

	// Skip files from export
	if ($context === 'export' && $field->type === 'file') {
		continue;
	}

	$value = $user->$key ?? null;
	?>
	<dt>{$field.label}</dt>

	{* Skip according to management access rules *}
	{if $context === 'manage' && !$session->canAccess(Session::SECTION_USERS, $field.management_access_level)}
		<dd><em>**Caché**</em></dd>
		<?php continue; ?>
	{/if}

	<dd>
		{if $field.type == 'checkbox'}
			{if $value}
				{icon shape="check"} Oui
			{else}
				{icon shape="uncheck"} Non
			{/if}
		{elseif $field.type == 'file'}
			<?php
			$edit = ($field->user_access_level === Session::ACCESS_WRITE || $context === 'manage');
			?>
			{include file="common/files/_context_list.tpl" path="%s/%s"|args:$user_files_path:$key}
		{elseif empty($value)}
			<em>(Non renseigné)</em>
		{elseif $field.type == 'email'}
			<a href="mailto:{$value|escape:'url'}">{$value}</a>
			{if !DISABLE_EMAIL && $show_message_button && !$email_button++}
				{linkbutton href="!users/message.php?id=%d"|args:$data.id label="Envoyer un message" shape="mail" target="_dialog"}
			{/if}
		{elseif $field.type == 'multiple'}
			<ul>
			{foreach from=$field.options key="b" item="name"}
				{if (int)$value & (0x01 << (int)$b)}
					<li>{$name}</li>
				{/if}
			{/foreach}
			</ul>
		{else}
			{if in_array($key, $id_fields)}<strong>{/if}
			{user_field field=$field value=$value user_id=$user.id}
			{if in_array($key, $id_fields)}</strong>{/if}
		{/if}
	</dd>
		{if $field.type == 'email' && $value}
		<?php
		$email = Email\Addresses::getOrCreate($value);
		$address = rawurlencode($value);
		?>
		<dt>Statut e-mail</dt>
		<dd>
			{tag color=$email->getStatusColor() label=$email->getStatusLabel()}
			{linkbutton target="_dialog" label="Détails" href="!users/email/address.php?address=%s"|args:$address shape="mail"}
		</dd>
		{/if}
	{/foreach}
</dl>
