{if !empty($page.titre) && $can_read}
    {include file="admin/_head.tpl" title=$page.titre current="wiki"}
{else}
    {include file="admin/_head.tpl" title="Wiki" current="wiki"}
{/if}

<ul class="actions">
    <li><a href="{$www_url}admin/wiki/"><strong>Wiki</strong></a></li>
    <li><a href="{$www_url}admin/wiki/chercher.php">Rechercher</a></li>
    {if $can_edit}
        <li><a href="{$www_url}admin/wiki/editer.php?id={$page.id|escape}">Éditer</a></li>
    {/if}
    {if $can_read && $page && $page.contenu}
        <li><a href="{$www_url}wiki/revisions.php?id={$page.id|escape}">Historique</a>
        {if $page.droit_lecture == Garradin_Wiki::LECTURE_PUBLIC}
            <li><a href="{$www_url}{$page.uri|escape}">Voir sur le site</a>
        {/if}
    {/if}
</ul>

{if !$can_read}
    <p class="alert">Vous n'avez pas le droit de lire cette page.</p>
{else}
    <div class="breadCrumbs">
        <ul>
            <li><a href="./">Wiki</a></li>
            {if !empty($breadcrumbs)}
            {foreach from=$breadcrumbs item="crumb"}
            <li><a href="?{$crumb.uri|escape}">{$crumb.titre|escape}</a></li>
            {/foreach}
            {/if}
        </ul>
    </div>

    {if !$page}
        <p class="error">
            Cette page n'existe pas.
        </p>

        {if $can_edit}
        <form method="post" action="{$www_url}admin/wiki/creer.php">
            <p class="submit">
                {csrf_field key="wiki_create"}
                <input type="hidden" name="titre" value="{$uri|escape}" />
                <input type="submit" name="create" value="Créer cette page" />
            </p>
        </form>
        {/if}
    {else}

        {if !$page.contenu}
            <p class="alert">Cette page est vide, cliquez sur « Éditer » pour la modifier.</p>
        {else}
            {if !empty($children)}
            <div class="wikiChildren">
                <h4>Dans cette rubrique</h4>
                <ul>
                {foreach from=$children item="child"}
                    <li><a href="?{$child.uri|escape}">{$child.titre|escape}</a></li>
                {/foreach}
                </ul>
            </div>
            {/if}

            {if $page.contenu.chiffrement}
                <noscript>
                    <div class="error">
                        Vous dever activer javascript pour pouvoir déchiffrer cette page.
                    </div>
                </noscript>
                <div class="wikiContent" id="wikiEncrypted">
                    <p>Cette page est chiffrée.</p>
                </div>
            {else}
                <div class="wikiContent">
                    {$page.contenu.contenu|format_wiki|liens_wiki:'?'}
                </div>
            {/if}

            <p class="wikiFooter">
                Dernière modification le {$page.date_modification|date_fr:'d/m/Y à H:i'}
                {if $user.droits.wiki == Garradin_Membres::DROIT_ADMIN}
                par <a href="{$www_url}admin/membres/fiche.php?id={$page.contenu.id_auteur|escape}">{$auteur|escape}</a>
                {/if}
            </p>
        {/if}
    {/if}
{/if}


{include file="admin/_foot.tpl"}