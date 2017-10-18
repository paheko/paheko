{include file="admin/_head.tpl" title="Liste des membres" current="membres" js=1}

{if $session->canAccess('membres', Garradin\Membres::DROIT_ECRITURE)}
<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/">Liste des membres</a></li>
    <li><a href="{$admin_url}membres/recherche.php">Recherche avancée</a></li>
    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}
        <li><a href="{$admin_url}membres/import.php">Import &amp; export</a></li>
        <li><a href="{$admin_url}membres/recherche_sql.php">Recherche par requête SQL</a></li>
    {/if}
</ul>
{/if}

{if $sent}
    <p class="confirm">Votre message a été envoyé.</p>
{/if}

{if !empty($membres_cats)}
<form method="get" action="{$self_url}" class="shortFormRight">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" id="f_cat" onchange="this.form.submit();">
            <option value="0" {if $current_cat == 0} selected="selected"{/if}>-- Toutes</option>
        {foreach from=$membres_cats key="id" item="nom"}
            {if $session->canAccess('membres', Garradin\Membres::DROIT_ECRITURE)
                || !array_key_exists($id, $membres_cats_cachees)}
            <option value="{$id}"{if $current_cat == $id} selected="selected"{/if}>{$nom}</option>
            {/if}
        {/foreach}
        </select>
        <noscript><input type="submit" value="Filtrer &rarr;" /></noscript>
    </fieldset>
</form>
{/if}

<form method="get" action="{$admin_url}membres/{if $session->canAccess('membres', Garradin\Membres::DROIT_ECRITURE)}recherche.php{/if}" class="shortFormLeft">
    <fieldset>
        <legend>Rechercher un membre</legend>
        <input type="text" name="r" value="" />
        <input type="submit" value="Chercher &rarr;" />
    </fieldset>
</form>

{if $session->canAccess('membres', Garradin\Membres::DROIT_ECRITURE)}

    <form method="post" action="action.php" class="memberList">

    {if !empty($liste)}
    <table class="list">
        <thead class="userOrder">
            <tr>
                {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" title="Tout cocher / décocher" /></td>{/if}
                {foreach from=$champs key="c" item="champ"}
                    <td class="{if $order == $c} cur {if $desc}desc{else}asc{/if}{/if}">{if $c == "numero"}#{else}{$champ.title}{/if} <a href="?o={$c}&amp;a&amp;cat={$current_cat}" class="icn up">&uarr;</a><a href="?o={$c}&amp;d&amp;cat={$current_cat}" class="icn dn">&darr;</a></td>
                {/foreach}
                <td></td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}<td class="check"><input type="checkbox" name="selected[]" value="{$membre.id}" /></td>{/if}
                    {foreach from=$champs key="c" item="cfg"}
                        <td>
                            {if $c == $config.champ_identite}<a href="{$admin_url}membres/fiche.php?id={$membre.id}">{/if}
                            {$membre->$c|raw|display_champ_membre:$cfg}
                            {if $c == $config.champ_identite}</a>{/if}
                        </td>
                    {/foreach}
                    <td class="actions">
                        <a class="icn" href="{$admin_url}membres/fiche.php?id={$membre.id}" title="Fiche membre">👤</a>
                        <a class="icn" href="{$admin_url}membres/modifier.php?id={$membre.id}" title="Modifier la fiche membre">✎</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}
    <p class="actions">
        <em>Pour les membres cochés :</em>
        <input type="submit" name="move" value="Changer de catégorie" />
        <input type="submit" name="delete" value="Supprimer" />
        {csrf_field key="membres_action"}
    </p>
    {/if}

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
    {else}
    <p class="alert">
        Aucun membre trouvé.
    </p>
    {/if}

    </form>
{else}
    {if !empty($liste)}
    <table class="list">
        <thead>
            <th>Membre</th>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    <th>{$membre.identite}</th>
                    <td class="actions">
                        {if !empty($membre.email)}<a href="{$www_url}admin/membres/message.php?id={$membre.id}">Envoyer un message</a>{/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if !empty($pagination_url)}
        {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
    {/if}

    {else}
    <p class="alert">
        Aucun membre trouvé.
    </p>
    {/if}
{/if}

{include file="admin/_foot.tpl"}