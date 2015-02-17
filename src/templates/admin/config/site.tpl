{include file="admin/_head.tpl" title="Configuration — Site public" current="config" js=1}

{if $error && $error != 'OK'}
    <p class="error">
        {$error|escape}
    </p>
{/if}

{include file="admin/config/_menu.tpl" current="site"}

{if isset($edit)}
    <form method="post" action="{$self_url|escape}">
        <h3>Éditer un squelette</h3>

        {if $error == 'OK'}
        <p class="confirm">
            Modifications enregistrées.
        </p>
        {/if}

        <fieldset class="skelEdit">
            <legend>{$edit.file|escape}</legend>
            <p>
                <textarea name="content" cols="90" rows="50" id="f_content">{form_field name=content data=$edit}</textarea>
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key=$csrf_key}
            <input type="submit" name="save" value="Enregistrer &rarr;" />
        </p>

    </form>

    <script type="text/javascript">
    var doc_url = "{$admin_url}doc/skel/";
    var skel_list = {$sources_json};
    var skel_current = "{$edit.file|escape}";
    </script>
    <script type="text/javascript" src="{$admin_url}static/scripts/skel_editor.js"></script>
{else}
    <div class="templatesList">
        <h3>Squelettes du site</h3>
        <ul>
        {foreach from=$sources item="source"}
            <li><a href="?edit={$source|escape:'url'}">{$source|escape}</a></li>
        {/foreach}
        </ul>
    </div>
{/if}

{include file="admin/_foot.tpl"}