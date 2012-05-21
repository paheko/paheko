{include file="admin/_head.tpl" title="Configuration" current="config"}

{if $error && $error != 'OK'}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<ul class="actions">
    <li><a href="{$www_url}admin/config/">Général</a></li>
    <li><a href="{$www_url}admin/config/membres.php">Membres</a></li>
    <li class="current"><a href="{$www_url}admin/config/site.php">Site public</a></li>
</ul>

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
                <textarea name="content" cols="90" rows="50">{form_field name=content data=$edit}</textarea>
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key=$csrf_key}
            <input type="submit" name="save" value="Enregistrer &rarr;" />
        </p>

    </form>
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