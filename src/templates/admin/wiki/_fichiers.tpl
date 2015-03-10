{include file="admin/_head.tpl" title="Inclure un fichier" current="wiki" body_id="transparent" is_popup=true js=1}

 <form method="post" enctype="multipart/form-data" action="{$self_url|escape}" id="f_upload">
    <fieldset>
        <legend>Téléverser un fichier</legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size|escape}" id="f_maxsize" />
        <dl>
            <dd class="help">Taille maximale : {$max_size|format_bytes}</dd>
            <dd class="fileUpload"><input type="file" name="fichier" id="f_fichier" data-hash-check /></dd>
        </dl>
        <p class="submit">
            {csrf_field key="wiki_upload_`$page.id`"}
            <input type="submit" id="f_submit" value="Envoyer le fichier" />
        </p>
    </fieldset>
</form>

<script type="text/javascript">
{literal}
uploadHelper($('#f_fichier'), {
    width: 1920,
    height: 1920,
    resize: true,
    bytes: 'o',
    size_error_msg: 'Le fichier %file fait %size, soit plus que la taille maximale autorisée de %max_size.'
});
{/literal}
</script>

{include file="admin/_foot.tpl"}