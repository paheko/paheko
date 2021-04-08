<?php
assert(isset($data, $champs, $show_message_button));
$user_files_path = (new Membres)->getAttachementsDirectory($data->id);
?>

<dl class="describe">
	{foreach from=$champs key="c" item="c_config"}
	<dt>{$c_config.title}</dt>
	<dd>
		{if $c_config.type == 'checkbox'}
			{if $data->$c}Oui{else}Non{/if}
		{elseif $c_config.type == 'file'}
			<?php
			$edit = ($c_config->editable || $mode == 'edit');
			?>
			{include file="common/files/_context_list.tpl" limit=1 path="%s/%s"|args:$user_files_path,$c}
		{elseif empty($data->$c)}
			<em>(Non renseigné)</em>
		{elseif $c == $c_config.champ_identite}
			<strong>{$data->$c}</strong>
		{elseif $c_config.type == 'email'}
			<a href="mailto:{$data->$c|escape:'url'}">{$data->$c}</a>
			{if $c == 'email' && $show_message_button}
				{linkbutton href="!membres/message.php?id=%d"|args:$data.id label="Envoyer un message" shape="mail"}
			{/if}
		{elseif $c_config.type == 'tel'}
			<a href="tel:{$data->$c}">{$data->$c|format_tel}</a>
		{elseif $c_config.type == 'country'}
			{$data->$c|get_country_name}
		{elseif $c_config.type == 'date'}
			{$data->$c|date_short}
		{elseif $c_config.type == 'datetime'}
			{$data->$c|date}
		{elseif $c_config.type == 'password'}
			*******
		{elseif $c_config.type == 'multiple'}
			<ul>
			{foreach from=$c_config.options key="b" item="name"}
				{if $data->$c & (0x01 << $b)}
					<li>{$name}</li>
				{/if}
			{/foreach}
			</ul>
		{else}
			{$data->$c|escape|rtrim|nl2br}
		{/if}
	</dd>
	{/foreach}
</dl>
