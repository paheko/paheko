{include file="admin/_head.tpl" title=$page.titre current="wiki"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset class="wikiMain">
        <legend>Informations générales</legend>
        <dl>
            <dt><label for="f_titre">Titre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="titre" id="f_titre" value="{form_field data=$page name=titre}" /></dd>
            <dt><label for="f_uri">Adresse unique</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">
                Chaque page doit comporter une adresse unique qui permet de l'identifier.
                Cette adresse ne peut comporter que des lettres, des chiffres, des tirets et des tirets bas.
            </dd>
            <dd><input type="text" name="uri" id="f_uri" value="{form_field data=$page name=uri}" /></dd>
            <dt><label for="f_parent">Cette page est une sous-rubrique de...</label></dt>
            <dd>
                <select name="parent" id="f_parent">
                    <option value="0">- la racine du site</option>
                </select>
            </dd>
        </dl>
    </fieldset>

    <fieldset class="wikiRights">
        <legend>Droits d'accès</legend>
        <dl>
            <dt><label for="f_droit_lecture_public">Cette page est visible :</label></dt>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_public" value="{Garradin_Wiki::LECTURE_PUBLIC}" />
                <label for="f_droit_lecture_public"><strong>Sur le site de l'association</strong></label>
                &mdash; cette page apparaîtra sur le site public de l'association, accessible à tous les visiteurs
            </dd>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_normal" value="{Garradin_Wiki::LECTURE_NORMAL}" />
                <label for="f_droit_lecture_normal"><strong>Sur le wiki uniquement</strong></label>
                &mdash; seuls les membres ayant accès au wiki pourront la voir
            </dd>
            <dd>
                <input type="radio" name="droit_lecture" id="f_droit_lecture_categorie" value="{$user.id_categorie}" />
                <label for="f_droit_lecture_categorie"><strong>Aux membres de ma catégorie</strong></label>
                &mdash; seuls les membres de la même catégorie que moi pourront voir cette page
            </dd>
            <dt><label for="f_droit_ecriture_normal">Cette page peut être modifiée par :</label></dt>
            <dd>
                <input type="radio" name="droit_ecriture" id="f_droit_ecriture_normal" value="{Garradin_Wiki::ECRITURE_NORMAL}" />
                <label for="f_droit_ecriture_normal">Les membres qui ont accès au wiki</label>
            </dd>
            <dd>
                <input type="radio" name="droit_ecriture" id="f_droit_ecriture_categorie" value="{$user.id_categorie}" />
                <label for="f_droit_ecriture_categorie">Les membres de ma catégorie</label>
            </dd>
        </dl>
    </fieldset>


    <fieldset class="wikiText">
        <p>
            <textarea name="contenu" cols="70" rows="30">{form_field data=$page name=contenu}</textarea>
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="wiki_edit_`$page.id`"}
        <input type="hidden" name="revision_edition" value="{form_field name=revision_edition default=$page.revision}" />
        <input type="hidden" name="debut_edition" value="{form_field name=debut_edition default=$time}" />
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>


{include file="admin/_foot.tpl"}