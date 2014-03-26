{include file="admin/_head.tpl" title="Configuration" current="config"}

{if $error}
    {if $error == 'OK'}
    <p class="confirm">
        La configuration a bien été enregistrée.
    </p>
    {else}
    <p class="error">
        {$error|escape}
    </p>
    {/if}
{/if}

{include file="admin/config/_menu.tpl" current="index"}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Garradin</legend>
        <dl>
            <dt>Version installée</dt>
            <dd class="help">{$garradin_version|escape} <a href="{Garradin\WEBSITE}">[Vérifier la disponibilité d'une nouvelle version]</a></dd>
            <dt>Informations système</dt>
            <dd class="help">PHP version {$php_version|escape} — SQLite version {$sqlite_version|escape}</dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Informations sur l'association</legend>
        <dl>
            <dt><label for="f_nom_asso">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom_asso" id="f_nom_asso" required="required" value="{form_field data=$config name=nom_asso}" /></dd>
            <dt><label for="f_email_asso">Adresse E-Mail</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="email" name="email_asso" id="f_email_asso" required="required" value="{form_field data=$config name=email_asso}" /></dd>
            <dt><label for="f_adresse_asso">Adresse postale</label></dt>
            <dd><textarea cols="50" rows="5" name="adresse_asso" id="f_adresse_asso">{form_field data=$config name=adresse_asso}</textarea></dd>
            <dt><label for="f_site_asso">Site web</label></dt>
            <dd><input type="url" name="site_asso" id="f_site_asso" value="{form_field name=site_asso data=$config}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Localisation</legend>
        <dl>
            <dt><label for="f_monnaie">Monnaie</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="monnaie" id="f_monnaie" required="required" value="{form_field name=monnaie data=$config}" size="5" /></dd>
            <dt><label for="f_pays">Pays</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="pays" id="f_pays" required="required">
                {foreach from=$pays key="cc" item="nom"}
                    <option value="{$cc|escape}"{if $cc == $config.pays} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Envois par E-Mail</legend>
        <dl>
            <dt><label for="f_email_envoi_automatique">Adresse E-Mail expéditeur des messages automatiques</label></dt>
            <dd><input type="text" name="email_envoi_automatique" id="f_email_envoi_automatique" value="{form_field data=$config name=email_envoi_automatique}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Wiki</legend>
        <dl>
            <dt><label for="f_accueil_wiki">Page d'accueil du wiki</label> 
                <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>Indiquer ici l'adresse unique de la page qui sera utilisée comme page d'accueil du wiki.</dd>
            <dd><input type="text" name="accueil_wiki" id="f_accueil_wiki" required="required" value="{form_field data=$config name=accueil_wiki}" /></dd>
            <dt><label for="f_accueil_connexion">Page d'accueil à la connexion</label> 
                <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>Indiquer ici l'adresse unique de la page qui sera affichée à la connexion d'un membre.</dd>
            <dd><input type="text" name="accueil_connexion" id="f_accueil_connexion" required="required" value="{form_field data=$config name=accueil_connexion}" /></dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Membres</legend>
        <dl>
            <dt><label for="f_categorie_membres">Catégorie par défaut des nouveaux membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="categorie_membres" required="required" id="f_categorie_membres">
                {foreach from=$membres_cats key="id" item="nom"}
                    <option value="{$id|escape}"{if $config.categorie_membres == $id} selected="selected"{/if}>{$nom|escape}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_champ_identite">Champ utilisé pour définir l'identité des membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">Ce champ des fiches membres sera utilisé comme identité du membre dans les emails, les fiches, les pages, etc.</dd>
            <dd>
                <select name="champ_identite" required="required" id="f_champ_identite">
                    {foreach from=$champs key="c" value="champ"}
                        <option value="{$c|escape}" {form_field selected=$c name="champ_identite" data=$config}>{$champ.title|escape}</option>
                    {/foreach}
                </select>
            </dd>
            <dt><label for="f_champ_identifiant">Champ utilisé comme identifiant de connexion</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">Ce champ des fiches membres sera utilisé en guise d'identifiant pour se connecter à Garradin. Pour cela le champ doit être unique (pas de doublons).</dd>
            <dd>
                <select name="champ_identifiant" required="required" id="f_champ_identifiant">
                    {foreach from=$champs key="c" value="champ"}
                        <option value="{$c|escape}" {form_field selected=$c name="champ_identifiant" data=$config}>{$champ.title|escape}</option>
                    {/foreach}
                </select>
            </dd>
            
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="config"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}