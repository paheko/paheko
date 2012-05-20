{if !empty($page.titre) && $can_read}
    {include file="admin/_head.tpl" title=$page.titre current="wiki"}
{else}
    {include file="admin/_head.tpl" title="Wiki" current="wiki"}
{/if}

{if !$can_read}
    <p class="alert">Vous n'avez pas le droit de lire cette page.</p>
{elseif !$page}
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
            Dernière modification le {$page.date_modification|date_fr:'d/m/Y à H:i'}.
            {*| <a href="revisions.php?id={$page.id|escape}">Liste des modifications</a>*}
        </p>
    {/if}

    {if $can_edit}
    <form method="get" action="{$www_url}admin/wiki/editer.php">
        <p class="submit">
            <input type="hidden" name="id" value="{$page.id|escape}" />
            <input type="submit" value="Éditer" />
        </p>
    </form>
    {/if}
{/if}


{include file="admin/_foot.tpl"}