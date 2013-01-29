{include file="admin/_head.tpl" title="Recherche de membre" current="membres"}

<form method="get" action="{$admin_url}membres/recherche.php" class="searchMember">
    <fieldset>
        <legend>Rechercher un membre</legend>
        <dl>
            <dt><label for="f_champ">Champ</label></dt>
            <dd>
                <select name="c" id="f_champ">
                    {foreach from=$champs key="k" item="v"}
                    <option value="{$k|escape}"{form_field name="c" default=$champ selected=$k}>{$v.title|escape}</option>
                    {/foreach}
                </select>
            </dd>
            <dt><label for="f_texte">Recherche</label></dt>
            <dd><input id="f_texte" type="text" name="r" value="{$recherche|escape}" /></dd>
        </dl>
        <p class="submit">
            <input type="submit" value="Chercher &rarr;" />
        </p>
    </fieldset>
</form>

{if $user.droits.membres >= Garradin\Membres::DROIT_ECRITURE}

    <form method="post" action="{$admin_url}membres/action.php" class="memberList">

    {if !empty($liste)}
    <table class="list">
        <thead>
            {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}<td class="check"><input type="checkbox" value="Tout cocher / décocher" onclick="checkUncheck();" /></td>{/if}
            <td></td>
            <td>{$titre_champ|escape}</td>
            <td>Cotisation</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}<td class="check"><input type="checkbox" name="selected[]" value="{$membre.id|escape}" /></td>{/if}
                    <td class="num"><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">{$membre.id|escape}</a></th>
                    <td>{$membre[$champ]|escape}</td>
                    {if empty($membre.date_cotisation)}
                        <td class="error">jamais réglée</td>
                    {elseif $membre.date_cotisation > strtotime('12 months ago')}
                        <td class="confirm">à jour</td>
                    {else}
                        <td class="alert">en retard</td>
                    {/if}
                    <td class="actions">
                        {if !empty($membre.email)}<a class="icn" href="{$www_url}admin/membres/message.php?id={$membre.id|escape}" title="Envoyer un message">✉</a> {/if}
                        <a class="icn" href="modifier.php?id={$membre.id|escape}">✎</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {if $user.droits.membres == Garradin\Membres::DROIT_ADMIN}
    <p class="checkUncheck">
        <input type="button" value="Tout cocher / décocher" onclick="checkUncheck();" />
    </p>
    <p class="actions">
        <em>Pour les membres cochés :</em>
        <input type="submit" name="move" value="Changer de catégorie" />
        <input type="submit" name="delete" value="Supprimer" />
        {csrf_field key="membres_action"}
    </p>
    {/if}

    {else}
    <p class="alert">
        Aucun membre trouvé.
    </p>
    {/if}

    </form>

    <script type="text/javascript">
    {literal}
    (function() {
        var checked = false;

        window.checkUncheck = function()
        {
            var elements = document.getElementsByTagName('input');
            var el_length = elements.length;

            for (i = 0; i < el_length; i++)
            {
                var elm = elements[i];

                if (elm.type == 'checkbox')
                {
                    if (checked)
                        elm.checked = false;
                    else
                        elm.checked = true;
                }
            }

            checked = checked ? false : true;
            return true;
        }
    }())
    {/literal}
    </script>
{else}
    {if !empty($liste)}
    <table class="list">
        <thead>
            <th>Nom</th>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="membre"}
                <tr>
                    <th>{$membre.nom|escape}</th>
                    <td class="actions">
                        {if !empty($membre.email)}<a href="{$www_url}admin/membres/message.php?id={$membre.id|escape}">Envoyer un message</a>{/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <p class="info">
        Aucune membre trouvé.
    </p>
    {/if}
{/if}

{include file="admin/_foot.tpl"}