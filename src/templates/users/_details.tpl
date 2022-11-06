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
				{linkbutton href="!users/message.php?id=%d"|args:$data.id label="Envoyer un message" shape="mail"}
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
			{display_dynamic_field field=$field value=$value}
		{/if}
	</dd>
		{if $field.type == 'email' && $value && ($email = Email\Emails::getEmail($value))}
		<dt>Statut e-mail</dt>
		<dd>
			{if $email.optout}
				<b class="alert">{icon shape="alert"}</b> Ne souhaite plus recevoir de messages
				<br/>{linkbutton target="_dialog" label="Rétablir l'envoi à cette adresse" href="emails.php?verify=%s"|args:$value shape="check"}
			{elseif $email.invalid}
				<b class="error">{icon shape="alert"} Adresse invalide</b>
			{elseif $email->hasReachedFailLimit()}
				<b class="error">{icon shape="alert"} Trop d'erreurs</b>
			{elseif $email.verified}
				<b class="confirm">{icon shape="check" class="confirm"}</b> Adresse vérifiée
			{else}
				Adresse non vérifiée
			{/if}
			{if $email.fail_log}
				<br /><span class="help">{$email.fail_log|escape|nl2br}</span>
			{/if}
		</dd>
		{/if}
	{/foreach}
</dl>
