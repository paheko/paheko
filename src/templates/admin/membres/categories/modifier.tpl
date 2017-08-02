{include file="admin/_head.tpl" title="Modifier une catégorie" current="membres/categories"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Informations générales</legend>
        <dl>
            <dt><label for="f_nom">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field data=$cat name=nom}" required="required" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" rows="5" cols="50">{form_field data=$cat name=description}</textarea></dd>
            <dt>
                <input type="checkbox" name="cacher" value="1" id="f_cacher" {if $cat.cacher}checked="checked"{/if} />
                <label for="f_cacher">Catégorie cachée</label>
            </dt>
            <dd class="help">
                Si coché cette catégorie ne sera visible qu'aux administrateurs et ne recevra pas
                de messages collectifs ou de rappels.
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Cotisation obligatoire</legend>
        <dl>
            <dt><label for="f_id_cotisation_obligatoire">Cotisation obligatoire</label></dt>
            <dd>
                <select name="id_cotisation_obligatoire" id="f_id_cotisation_obligatoire">
                    <option value="">-- Non</option>
                    {foreach from=$cotisations item="cotisation"}
                    <option value="{$cotisation.id}" {form_field name="id_cotisation_obligatoire" selected=$cotisation.id data=$cat}>
                        {$cotisation.intitule} 
                        — {$cotisation.montant|escape|html_money} {$config.monnaie}
                        — {if $cotisation.duree}pour {$cotisation.duree} jours
                        {elseif $cotisation.debut}
                            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
                        {else}
                            ponctuelle
                        {/if}
                    </option>
                    {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Droits</legend>
        <dl class="droits">
            <dt><label for="f_droit_connexion_aucun">Les membres de cette catégorie peuvent-ils se connecter ?</label></dt>
            {if $readonly}
                <dd class="help">
                    Il n'est pas possible de désactiver ce droit pour votre propre catégorie.
                </dd>
            {/if}
            <dd>
                <input type="radio" name="droit_connexion" value="{$membres::DROIT_AUCUN}" id="f_droit_connexion_aucun" {if $cat.droit_connexion == $membres::DROIT_AUCUN}checked="checked"{/if} {$readonly} />
                <label for="f_droit_connexion_aucun"><b class="aucun">C</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_connexion" value="{$membres::DROIT_ACCES}" id="f_droit_connexion_acces" {if $cat.droit_connexion == $membres::DROIT_ACCES}checked="checked"{/if} {$readonly} />
                <label for="f_droit_connexion_acces"><b class="acces">C</b> Oui</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_inscription_aucun">Les membres de cette catégorie peuvent-ils s'inscrire d'eux-même ?</label></dt>
            <dd>
                <input type="radio" name="droit_inscription" value="{$membres::DROIT_AUCUN}" id="f_droit_inscription_aucun" {if $cat.droit_inscription == $membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_inscription_aucun"><b class="aucun">I</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_inscription" value="{$membres::DROIT_ACCES}" id="f_droit_inscription_acces" {if $cat.droit_inscription == $membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_inscription_acces"><b class="acces">I</b> Oui</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_membres_aucun">Gestion des membres :</label></dt>
            {if $readonly}
                <dd class="help">
                    Il n'est pas possible de désactiver ce droit pour votre propre catégorie.
                </dd>
            {/if}
            <dd>
                <input type="radio" name="droit_membres" value="{$membres::DROIT_AUCUN}" id="f_droit_membres_aucun" {if $cat.droit_membres == $membres::DROIT_AUCUN}checked="checked"{/if} {$readonly} />
                <label for="f_droit_membres_aucun"><b class="aucun">M</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{$membres::DROIT_ACCES}" id="f_droit_membres_acces" {if $cat.droit_membres == $membres::DROIT_ACCES}checked="checked"{/if} {$readonly} />
                <label for="f_droit_membres_acces"><b class="acces">M</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{$membres::DROIT_ECRITURE}" id="f_droit_membres_ecriture" {if $cat.droit_membres == $membres::DROIT_ECRITURE}checked="checked"{/if} {$readonly} />
                <label for="f_droit_membres_ecriture"><b class="ecriture">M</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_membres" value="{$membres::DROIT_ADMIN}" id="f_droit_membres_admin" {if $cat.droit_membres == $membres::DROIT_ADMIN}checked="checked"{/if} {$readonly} />
                <label for="f_droit_membres_admin"><b class="admin">M</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_compta_aucun">Comptabilité :</label></dt>
            <dd>
                <input type="radio" name="droit_compta" value="{$membres::DROIT_AUCUN}" id="f_droit_compta_aucun" {if $cat.droit_compta == $membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_compta_aucun"><b class="aucun">€</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{$membres::DROIT_ACCES}" id="f_droit_compta_acces" {if $cat.droit_compta == $membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_compta_acces"><b class="acces">€</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{$membres::DROIT_ECRITURE}" id="f_droit_compta_ecriture" {if $cat.droit_compta == $membres::DROIT_ECRITURE}checked="checked"{/if} />
                <label for="f_droit_compta_ecriture"><b class="ecriture">€</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_compta" value="{$membres::DROIT_ADMIN}" id="f_droit_compta_admin" {if $cat.droit_compta == $membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_compta_admin"><b class="admin">€</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_wiki_aucun">Wiki :</label></dt>
            <dd>
                <input type="radio" name="droit_wiki" value="{$membres::DROIT_AUCUN}" id="f_droit_wiki_aucun" {if $cat.droit_wiki == $membres::DROIT_AUCUN}checked="checked"{/if} />
                <label for="f_droit_wiki_aucun"><b class="aucun">W</b> Pas d'accès</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{$membres::DROIT_ACCES}" id="f_droit_wiki_acces" {if $cat.droit_wiki == $membres::DROIT_ACCES}checked="checked"{/if} />
                <label for="f_droit_wiki_acces"><b class="acces">W</b> Lecture uniquement</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{$membres::DROIT_ECRITURE}" id="f_droit_wiki_ecriture" {if $cat.droit_wiki == $membres::DROIT_ECRITURE}checked="checked"{/if} />
                <label for="f_droit_wiki_ecriture"><b class="ecriture">W</b> Lecture &amp; écriture</label>
            </dd>
            <dd>
                <input type="radio" name="droit_wiki" value="{$membres::DROIT_ADMIN}" id="f_droit_wiki_admin" {if $cat.droit_wiki == $membres::DROIT_ADMIN}checked="checked"{/if} />
                <label for="f_droit_wiki_admin"><b class="admin">W</b> Administration</label>
            </dd>
        </dl>
        <dl class="droits">
            <dt><label for="f_droit_config_aucun">Les membres de cette catégorie peuvent-ils modifier la configuration ?</label></dt>
            {if $readonly}
                <dd class="help">
                    Il n'est pas possible de désactiver ce droit pour votre propre catégorie.
                </dd>
            {/if}
            <dd>
                <input type="radio" name="droit_config" value="{$membres::DROIT_AUCUN}" id="f_droit_config_aucun" {if $cat.droit_config == $membres::DROIT_AUCUN}checked="checked"{/if} {$readonly} />
                <label for="f_droit_config_aucun"><b class="aucun">&#x2611;</b> Non</label>
            </dd>
            <dd>
                <input type="radio" name="droit_config" value="{$membres::DROIT_ADMIN}" id="f_droit_config_admin" {if $cat.droit_config == $membres::DROIT_ADMIN}checked="checked"{/if} {$readonly} />
                <label for="f_droit_config_admin"><b class="admin">&#x2611;</b> Oui</label>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_cat_"|cat:$cat.id}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}