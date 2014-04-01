{include file="admin/_head.tpl" title="Modifier un rappel automatique" current="membres/cotisations" js=1}

<ul class="actions">
    <li><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    <li class="current"><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}" id="f_add">

    <fieldset>
        <legend>Modifier un rappel automatique</legend>
        <dl>
            <dt><label for="f_id_cotisation">Cotisation associée</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="id_cotisation" id="f_id_cotisation" required="required">
                    <option value="">--</option>
                    {foreach from=$cotisations item="co"}
                    <option value="{$co.id|escape}" {form_field name="id_cotisation" selected=$co.id data=$rappel}>
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
            <dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet data=$rappel}" required="required" size="50" /></dd>
            <dt><label for="f_delai">Délai d'envoi</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><label><input type="radio" name="delai_choix" value="0" {form_field name="delai_choix" checked=0 default=0 data=$rappel} /> Le jour de l'expiration de la cotisation</label></dd>
            <dd>
                <input type="radio" name="delai_choix" id="f_delai_pre" value="-1" {form_field name="delai_choix" checked=-1 data=$rappel} />
                <input type="number" name="delai_pre" id="f_delai_pre_nb" step="1" min="1" max="900" size="4" id="f_delai" value="{form_field name=delai_pre data=$rappel default=30}" />
                <label for="f_delai_pre">jours avant expiration</label>
            </dd>
            <dd>
                <input type="radio" name="delai_choix" id="f_delai_post" value="1" {form_field name="delai_choix" checked=1 data=$rappel} /> 
                <input type="number" name="delai_post" id="f_delai_post_nb" step="1" min="1" max="900" size="4" id="f_delai" value="{form_field name=delai_post default=30 data=$rappel}" />
                <label for="f_delai_post">jours après expiration</label>
            </dd>
            <dt><label for="f_texte">Texte du mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><textarea name="texte" id="f_texte" cols="70" rows="15" required="required">{form_field name=texte data=$rappel}</textarea></dd>
            <dd class="help">Astuce : pour inclure dans le contenu du mail le nom du membre, utilisez #IDENTITE, pour inclure le délai de l'envoi utilisez #NB_JOURS.</dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_rappel_`$rappel.id`"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}