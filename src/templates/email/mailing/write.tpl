{include file="_head.tpl" title="Message collectif" current="users/mailing" hide_title=true}

{form_errors}

<form method="post" action="{$self_url}">


	<fieldset class="textEditor">
		{input type="textarea" name="content" cols=35 rows=25 required=true class="full-width"
				data-attachments=0 data-savebtn=1 data-preview-url="!email/mailing/write.php?id=%s&preview"|local_url|args:$mailing.id data-format="markdown" placeholder="Contenu du message…" default=$mailing.body}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript">
{literal}
$('#f_edit_sender').onclick = () => {
	g.toggle('.sender_default', false);
	g.toggle('.sender_custom', true);
}
{/literal}
{if !$mailing.sender_name}
g.toggle('.sender_custom', false);
{/if}
</script>


{include file="_foot.tpl"}