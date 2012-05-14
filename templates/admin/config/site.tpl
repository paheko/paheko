{include file="admin/_head.tpl" title="Configuration" current="config"}

{if $error}
    {if $error == 'OK'}
    <p class="confirm">
        La configuration a bien été enregistrée.
    </p>
    {else}
    <p class="error">
        {$error|escape}
    </p>
    {/if}
{/if}

<ul class="actions">
    <li><a href="{$www_url}admin/config/">Général</a></li>
    <li><a href="{$www_url}admin/config/membres.php">Membres</a></li>
    <li class="current"><a href="{$www_url}admin/config/site.php">Site public</a></li>
</ul>

<form method="post" action="{$self_url|escape}">

    <p class="submit">
        {csrf_field key="config_membres"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}