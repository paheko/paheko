{include file="admin/_head.tpl" title="Historique : %s"|args:$page.titre current="wiki"}

<ul class="actions">
    <li><a href="{$www_url}admin/wiki/?{$page.uri}">Voir la page</a></li>
</ul>

{if !empty($revisions)}
    <table class="list wikiRevisions">
    {foreach from=$revisions item="rev"}
        <tr>
            <td>
                {if $rev.chiffrement}
                    <del title="Contenu chiffré">chiffré</del>
                {else}
                    {if $rev.revision == $page.revision}
                        actu
                    {else}
                        <a href="?id={$page.id}&amp;diff={$rev.revision}.{$page.revision}">actu</a>
                    {/if}
                    |
                    {if $rev.revision == 1}
                        diff
                    {else}
                        <a href="?id={$page.id}&amp;diff={$rev.revision-1}.{$rev.revision}">diff</a>
                    {/if}
                {/if}
            </td>
            <th>{$rev.date|date_fr:'d/m/Y à H:i'}</th>
            <td>
                {if $session->canAccess('membres', Garradin\Membres::DROIT_ACCES)}
                <a href="{$www_url}admin/membres/fiche.php?id={$rev.id_auteur}">{$rev.nom_auteur}</a>
                {/if}
            </td>
            <td class="length">
                {$rev.taille} octets
                {if $rev.revision > 1 && !$rev.chiffrement}
                    {if $rev.diff_taille > 0}
                        <ins>(+{$rev.diff_taille})</ins>
                    {elseif $rev.diff_taille < 0}
                        <del>({$rev.diff_taille})</del>
                    {else}
                        <i>({$rev.diff_taille})</i>
                    {/if}
                {/if}
            </td>
            <td>
            {if $rev.modification}
                <em>{$rev.modification}</em>
            {/if}
            </td>
        </tr>
    {/foreach}
    </table>
{elseif !empty($diff)}
    <div class="wikiRevision revisionLeft">
        <h3>Version du {$rev1.date|date_fr:'d/m/Y à H:i'}</h3>
        {if $session->canAccess('membres', Garradin\Membres::DROIT_ACCES)}
            <h4>De <a href="{$www_url}admin/membres/fiche.php?id={$rev1.id_auteur}">{$rev1.nom_auteur}</a></h4>
        {/if}
        {if $rev1.modification}
            <p><em>{$rev1.modification}</em></p>
        {/if}
    </div>
    <div class="wikiRevision revisionRight">
        <h3>Version {if $rev2.revision == $page.revision}actuelle en date{/if} du {$rev2.date|date_fr:'d/m/Y à H:i'}</h3>
        {if $session->canAccess('membres', Garradin\Membres::DROIT_ACCES)}
            <h4>De <a href="{$www_url}admin/membres/fiche.php?id={$rev2.id_auteur}">{$rev2.nom_auteur}</a></h4>
        {/if}
        {if $rev2.modification}
            <p><em>{$rev2.modification}</em></p>
        {/if}
    </div>
    {diff old=$rev1.contenu new=$rev2.contenu}
{else}
    <p class="alert">
        Cette page n'a pas d'historique.
    </p>
{/if}


{include file="admin/_foot.tpl"}