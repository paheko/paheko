<?php
assert(isset($data, $champs, $show_message_button));
$user_files_path = (new Membres)->getAttachementsDirectory($data->id);
?>

<dl class="describe">
	{foreach from=$champs key="c" item="c_config"}
	<?php
	// Skip private fields from "my info" page
	if ($mode == 'user' && $c_config->private) {
		continue;
	}

	// Skip files from export
	if ($mode == 'export' && $c_config->type == 'file') {
		continue;
	}

	$value = $data->$c ?? null;
	?>
	<dt>{$c_config.title}</dt>
	<dd>
		{if $c_config.type == 'checkbox'}
			{if $value}Oui{else}Non{/if}
		{elseif $c_config.type == 'file'}
			<?php
			$edit = ($c_config->editable || $mode == 'edit');
			?>
			{include file="common/files/_context_list.tpl" path="%s/%s"|args:$user_files_path,$c}
		{elseif empty($value)}
			<em>(Non renseigné)</em>
		{elseif $c == $c_config.champ_identite}
			<strong>{$value}</strong>
		{elseif $c_config.type == 'email'}
			<a href="mailto:{$value|escape:'url'}">{$value}</a>
			{if $c == 'email' && $show_message_button}
				{linkbutton href="!membres/message.php?id=%d"|args:$data.id label="Envoyer un message" shape="mail"}
			{/if}
		{elseif $c_config.type == 'multiple'}
			<ul>
			{foreach from=$c_config.options key="b" item="name"}
				{if $value & (0x01 << $b)}
					<li>{$name}</li>
				{/if}
			{/foreach}
			</ul>
		{else}
			{$value|display_champ_membre:$c_config|raw}
		{/if}
	</dd>
		{if $c_config.type == 'email' && $value && ($email = Users\Emails::getEmail($value))}
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
