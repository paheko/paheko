{include file="admin/_head.tpl" title="Gestion des rappels automatiques" current="membres/cotisations" js=1}

<ul class="actions">
    <li><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    <li><a href="{$admin_url}membres/cotisations/rappels.php">État des rappels</a></li>
    <li class="current"><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
</ul>

{if empty($liste)}
    <p class="alert">Aucun rappel automatique n'est enregistré.</p>
{else}
    <table class="list">
        <thead>
            <td>Cotisation</td>
            <td>Délai de rappel</td>
            <th>Sujet</th>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="rappel"}
                <tr>
                    <td>
                        {$rappel.intitule|escape}
                        — {$rappel.montant|html_money} {$config.monnaie|escape}
                        — {if $rappel.duree}pour {$rappel.duree|escape} jours
                        {elseif $rappel.debut}
                            du {$rappel.debut|format_sqlite_date_to_french} au {$rappel.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </td>
                    <td>{$rappel.delai|abs|escape} jours {if $rappel.delai > 0}après{else}avant{/if} expiration</td>
                    <th><a href="{$admin_url}membres/cotisations/rappel.php?id={$rappel.id|escape}">{$rappel.sujet|escape}</a></th>
                    <td class="actions">
                        <a class="icn" href="{$admin_url}membres/cotisations/gestion/rappel_modifier.php?id={$rappel.id|escape}" title="Modifier">✎</a>
                        <a class="icn" href="{$admin_url}membres/cotisations/gestion/rappel_supprimer.php?id={$rappel.id|escape}" title="Supprimer">✘</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}" id="f_add">

    <fieldset>
        <legend>Ajouter un rappel automatique</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation associée</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_cotisation" id="f_id_cotisation" required="required">
                    <option value="">--</option>
                    {foreach from=$cotisations item="co"}
                    <option value="{$co.id|escape}" {form_field name="id_cotisation" selected=$co.id}>
                        {$co.intitule|escape}
                        — {$co.montant|html_money} {$config.monnaie|escape}
                        — {if $co.duree}pour {$co.duree|escape} jours
                        {elseif $co.debut}
                            du {$co.debut|format_sqlite_date_to_french} au {$co.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </option>
                    {/foreach}
                </select>
            </dd>
            <dt><label for="f_sujet">Sujet du mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet default=$default_subject}" required="required" size="50" /></dd>
            <dt><label for="f_delai">Délai d'envoi</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="delai" step="1" min="1" max="900" size="5" id="f_delai" value="{form_field name=delai default=30}" required="required" /> jours</dd>
            <dd><label><input type="radio" name="delai_pre" value="1" {form_field name="delai_pre" checked=1 default=1} /> Avant l'expiration de la cotisation</label></dd>
            <dd><label><input type="radio" name="delai_pre" value="0" {form_field name="delai_pre" checked=0} /> Après l'expiration de la cotisation</label></dd>
            <dt><label for="f_texte">Texte du mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><textarea name="texte" id="f_texte" cols="70" rows="15" required="required">{form_field name=texte default=$default_text}</textarea></dd>
            <dd class="help">Astuce : pour inclure dans le contenu du mail le nom du membre, utilisez #IDENTITE, pour inclure le délai de l'envoi utilisez #NB_JOURS.</dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="new_rappel"}
        <input type="submit" name="save" value="Ajouter &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}