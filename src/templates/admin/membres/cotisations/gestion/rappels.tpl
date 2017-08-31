{include file="admin/_head.tpl" title="Gestion des rappels automatiques" current="membres/cotisations" js=1}

<ul class="actions">
    <li><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    <li class="current"><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
</ul>

<p class="help">
    Les rappels automatiques sont envoyés aux membres disposant d'une adresse e-mail
    selon le délai défini. Il est possible de définir plusieurs rappels pour une même cotisation.
</p>

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
                        {$rappel.intitule}
                        — {$rappel.montant|escape|html_money} {$config.monnaie}
                        — {if $rappel.duree}pour {$rappel.duree} jours
                        {elseif $rappel.debut}
                            du {$rappel.debut|format_sqlite_date_to_french} au {$rappel.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </td>
                    <td>
                        {if $rappel.delai == 0}le jour de l'expiration
                        {else}
                            {$rappel.delai|abs}
                            {if abs($rappel.delai) > 1}jours{else}jour{/if}
                            {if $rappel.delai > 0}après{else}avant{/if}
                            expiration
                        {/if}
                    </td>
                    <th>{* FIXME liste des personnes ayant reçu ce rappel<a href="{$admin_url}membres/cotisations/rappel.php?id={$rappel.id}">{$rappel.sujet}</a>*}{$rappel.sujet}</th>
                    <td class="actions">
                        <a class="icn" href="{$admin_url}membres/cotisations/gestion/rappel_modifier.php?id={$rappel.id}" title="Modifier">✎</a>
                        <a class="icn" href="{$admin_url}membres/cotisations/gestion/rappel_supprimer.php?id={$rappel.id}" title="Supprimer">✘</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{/if}

{form_errors}

<form method="post" action="{$self_url}" id="f_add">

    <fieldset>
        <legend>Ajouter un rappel automatique</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation associée</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_cotisation" id="f_id_cotisation" required="required">
                    <option value="">--</option>
                    {foreach from=$cotisations item="co"}
                    <option value="{$co.id}" {form_field name="id_cotisation" selected=$co.id}>
                        {$co.intitule}
                        — {$co.montant|escape|html_money} {$config.monnaie}
                        — {if $co.duree}pour {$co.duree} jours
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
            <dd><label><input type="radio" name="delai_choix" value="0" {form_field name="delai_choix" checked=0 default=0} /> Le jour de l'expiration de la cotisation</label></dd>
            <dd>
                <input type="radio" name="delai_choix" id="f_delai_pre" value="-1" {form_field name="delai_choix" checked=-1} />
                <input type="number" name="delai_pre" id="f_delai_pre_nb" step="1" min="1" max="900" size="4" id="f_delai" value="{form_field name=delai_pre default=30}" />
                <label for="f_delai_pre">jours avant expiration</label>
            </dd>
            <dd>
                <input type="radio" name="delai_choix" id="f_delai_post" value="1" {form_field name="delai_choix" checked=1} /> 
                <input type="number" name="delai_post" id="f_delai_post_nb" step="1" min="1" max="900" size="4" id="f_delai" value="{form_field name=delai_post default=30}" />
                <label for="f_delai_post">jours après expiration</label>
            </dd>
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

<script type="text/javascript">
{literal}
(function () {
    $('#f_delai_pre_nb').onclick = function () {
        $('#f_delai_pre').checked = true;
    };
    $('#f_delai_post_nb').onclick = function () {
        $('#f_delai_post').checked = true;
    };
})();
{/literal}
</script>

{include file="admin/_foot.tpl"}