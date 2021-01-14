{include file="admin/_head.tpl" title="Recherche" current="wiki/chercher"}

<form method="get" action="{$admin_url}wiki/chercher.php" class="wikiSearch">
    <fieldset>
        <legend>Rechercher une page</legend>
        <p class="submit">
            <input type="text" name="q" value="{$recherche}" size="25" />
            {button type="submit" name="search" label="Chercher" shape="search" class="main"}
        </p>
    </fieldset>
</form>


{if !$recherche}
    <p class="block alert">
        Aucun terme recherché.
    </p>
{else}
    <p class="block alert">
        <strong>{$nb_resultats}</strong> pages trouvées pour «&nbsp;{$recherche}&nbsp;»
    </p>

    <div class="wikiResults">
    {foreach from=$resultats item="page"}
        <h3><a href="./?{$page.uri}">{$page.titre}</a></h3>
        <p>{$page.snippet|escape|clean_snippet}</p>
    {/foreach}
    </div>
{/if}

{include file="admin/_foot.tpl"}