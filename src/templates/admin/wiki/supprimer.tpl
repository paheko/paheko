{include file="admin/_head.tpl" title="Supprimer : `$page.titre`" current="wiki"}

<ul class="actions">
    <li><a href="{$www_url}admin/wiki/"><strong>Wiki</strong></a></li>
    <li><a href="{$www_url}admin/wiki/chercher.php">Rechercher</a></li>
    <li><a href="{$www_url}admin/wiki/?{$page.uri|escape}">Voir la page</a></li>
    <li><a href="{$www_url}admin/wiki/editer.php?id={$page.id|escape}">Éditer</a></li>
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer cette page du wiki ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la page «&nbsp;{$page.titre|escape}&nbsp;» ?
        </h3>
        <p class="help">
            La page ne pourra pas être supprimée si d'autres pages l'utilisent comme rubrique
            parente.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_wiki_"|cat:$page.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}