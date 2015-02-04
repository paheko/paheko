{include file="admin/_head.tpl" title="Inclure un fichier" current="wiki" body_id="popup" is_popup=true js=1}

 <form method="post" enctype="multipart/form-data" action="{$self_url|escape}" id="f_upload">
    <fieldset>
        <legend>Téléverser un fichier</legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="{$max_size|escape}" id="f_maxsize" />
        <dl>
            <dt><label for="f_fichier">Sélectionner un fichier</label></dt>
            <dd class="help">Taille maximale : {$max_size|format_bytes}</dd>
            <dd><input type="file" name="fichier" id="f_fichier" /></dd>
            <dt><label for="f_titre">Titre du fichier (description)</label></dt>
            <dd><input type="text" name="titre" id="f_titre" /></dd>
        </dl>
        <p class="submit">
            <input type="submit" id="f_submit" value="Envoyer le fichier" />
        </p>
    </fieldset>
</form>

{include file="admin/_foot.tpl"}