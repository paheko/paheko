{include file="admin/_head.tpl" title="Historique : `$page.titre`" current="wiki"}

<ul class="actions">
    <li><a href="{$www_url}admin/wiki/"><strong>Wiki</strong></a></li>
    <li><a href="{$www_url}admin/wiki/chercher.php">Rechercher</a></li>
    <li><a href="{$www_url}admin/wiki/?{$page.uri|escape}">Voir la page</a></li>
    {if $can_edit}
        <li><a href="{$www_url}admin/wiki/editer.php?id={$page.id|escape}">Éditer</a></li>
    {/if}
</ul>

{if !empty($revisions)}
    <table class="list wikiRevisions">
    {foreach from=$revisions item="rev"}
        <tr>
            <td>
                {if $rev.revision == $page.revision}
                    actu
                {else}
                    <a href="?id={$page.id|escape}&amp;diff={$rev.revision|escape}.{$page.revision|escape}">actu</a>
                {/if}
                |
                {if $rev.revision == 1}
                    diff
                {else}
                    <a href="?id={$page.id|escape}&amp;diff={math equation="x+1" x=$rev.revision}.{$rev.revision|escape}">diff</a>
                {/if}
            </td>
            <th>{$rev.date|date_fr:'d/m/Y à H:i'}</th>
            <td>
                {if $user.droits.membres >= Garradin_Membres::DROIT_ACCES}
                <a href="{$www_url}admin/membres/fiche.php?id={$rev.id_auteur|escape}">{$rev.nom_auteur|escape}</a>
                {/if}
            </td>
            <td class="length">
                {$rev.taille|escape} octets
                {if $rev.revision > 1}
                    {if $rev.diff_taille > 0}
                        <ins>(+{$rev.diff_taille|escape})</ins>
                    {elseif $rev.diff_taille < 0}
                        <del>({$rev.diff_taille|escape})</del>
                    {else}
                        <i>({$rev.diff_taille|escape})</i>
                    {/if}
                {/if}
            </td>
            <td>
                <em>{$rev.modification|escape}</em>
            </td>
        </tr>
    {/foreach}
    </table>
{elseif !empty($diff)}

{else}
    <p class="alert">
        Cette page n\'a pas d\'historique.
    </p>
{/if}


{include file="admin/_foot.tpl"}