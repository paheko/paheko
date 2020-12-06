{include file="admin/_head.tpl" title="Supprimer : %s"|args:$page.titre current="wiki"}

<nav class="tabs">
    <ul>
        <li><a href="{$admin_url}wiki/"><strong>Wiki</strong></a></li>
        <li><a href="{$admin_url}wiki/chercher.php">Rechercher</a></li>
        <li><a href="{$admin_url}wiki/?{$page.uri}">Voir la page</a></li>
        <li><a href="{$admin_url}wiki/editer.php?id={$page.id}">Éditer</a></li>
    </ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer cette page du wiki ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la page «&nbsp;{$page.titre}&nbsp;» ?
        </h3>
        <p class="help">
            La page ne pourra pas être supprimée si d'autres pages l'utilisent comme rubrique
            parente.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_wiki_"|cat:$page.id}
        {button type="submit" name="delete" label="Supprimer" shape="right" class="main"}
    </p>

</form>

{include file="admin/_foot.tpl"}