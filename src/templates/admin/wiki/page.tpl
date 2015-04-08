{if !empty($page.titre) && $can_read}
    {include file="admin/_head.tpl" title=$page.titre current="wiki" js=1}
{else}
    {include file="admin/_head.tpl" title="Wiki" current="wiki"}
{/if}

<ul class="actions">
    {if $user.droits.wiki >= Garradin\Membres::DROIT_ECRITURE}
        <li><a href="{$www_url}admin/wiki/creer.php?parent={if $config.accueil_wiki == $page.uri}0{else}{$page.id|escape}{/if}"><strong>Créer une nouvelle page</strong></a></li>
    {/if}
    {if $can_edit}
        <li><a href="{$www_url}admin/wiki/editer.php?id={$page.id|escape}">Éditer</a></li>
    {/if}
    {if $can_read && $page && $page.contenu}
        <li><a href="{$www_url}admin/wiki/historique.php?id={$page.id|escape}">Historique</a>
        {if $page.droit_lecture == Garradin\Wiki::LECTURE_PUBLIC}
            <li><a href="{$www_url}{$page.uri|escape}">Voir sur le site</a>
        {/if}
    {/if}
    {if $user.droits.wiki >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$www_url}admin/wiki/supprimer.php?id={$page.id|escape}">Supprimer</a></li>
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

        {if !$page.contenu}
            <p class="alert">Cette page est vide, cliquez sur « Éditer » pour la modifier.</p>
        {else}

            {if $page.contenu.chiffrement}
                <noscript>
                    <div class="error">
                        Vous dever activer javascript pour pouvoir déchiffrer cette page.
                    </div>
                </noscript>
                <script type="text/javascript" src="{$admin_url}static/scripts/wiki-encryption.js"></script>
                <div id="wikiEncryptedMessage">
                    <p class="alert">Cette page est chiffrée.
                        <input type="button" onclick="return wikiDecrypt(false);" value="Entrer le mot de passe" />
                    </p>
                </div>
                <div class="wikiContent" style="display: none;" id="wikiEncryptedContent">
                    {$page.contenu.contenu|escape}
                </div>
            {else}
                <div class="wikiContent">
                    {$page.contenu.contenu|format_wiki|liens_wiki:'?'}
                </div>
            {/if}


            {if !empty($images) || !empty($fichiers)}
            <div class="wikiFiles">
                <h3>Fichiers liés à cette page</h3>

                {if !empty($images)}
                <ul class="gallery">
                    {foreach from=$images item="file"}
                        <li>
                            <figure>
                                <a href="{$file.url|escape}"><img src="{$file.thumb|escape}" alt="" title="{$file.nom|escape}" /></a>
                            </figure>
                        </li>
                    {/foreach}
                </ul>
                {/if}

                {if !empty($fichiers)}
                <ul class="files">
                    {foreach from=$fichiers item="file"}
                        <li>
                            <aside class="fichier" class="internal-file"><a href="{$file.url|escape}">{$file.nom|escape}</a>
                            <small>({$file.type|escape}, {$file.taille|format_bytes})</small>
                       </li>
                    {/foreach}
                </ul>
                {/if}
            </div>
            {/if}

            <p class="wikiFooter">
                Dernière modification le {$page.date_modification|date_fr:'d/m/Y à H:i'}
                {if $user.droits.membres >= Garradin\Membres::DROIT_ACCES}
                par <a href="{$www_url}admin/membres/fiche.php?id={$page.contenu.id_auteur|escape}">{$auteur|escape}</a>
                {/if}
            </p>
        {/if}
    {/if}
{/if}


{include file="admin/_foot.tpl"}