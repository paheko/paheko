{if $page}
    {include file="admin/_head.tpl" title=$page.titre current="wiki"}
{else}
    {include file="admin/_head.tpl" title="Wiki" current="wiki"}
{/if}

{if !$page}
    <p class="error">
        Cette page n'existe pas.
    </p>
{else}
    {if !$page.contenu}
        <p class="alert">Cette page est vide, cliquez sur « Éditer » pour la modifier.</p>
    {else}
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
                {$page.contenu.contenu|format_wiki}
            </div>
        {/if}

        <p class="wikiFooter">
            Dernière modification le {$page.date_modification|date_fr:'d/m/Y à H:i'} par AUTEUR.
            | <a href="revisions.php?id={$page.id|escape}">Liste des modifications</a>
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