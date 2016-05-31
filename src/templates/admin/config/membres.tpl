{include file="admin/_head.tpl" title="Configuration — Fiche membres" current="config" js=1}

{include file="admin/config/_menu.tpl" current="membres"}

{if $error}
    {if $error == 'OK'}
    <p class="confirm">
        La configuration a bien été enregistrée.
    </p>
    {elseif $error == 'ADD_OK'}
    <p class="confirm">
        Le champ a été ajouté à la fin de la liste.
    </p>
    {else}
    <p class="error">
        {$error|escape}
    </p>
    {/if}
{/if}

{if $review}
    <fieldset>
        <legend>Fiche membre exemple</legend>
        <dl>
            {foreach from=$champs item="champ" key="nom"}
                {if $nom == 'passe'}{continue}{/if}
                {html_champ_membre config=$champ name=$nom disabled=true}
                {if empty($champ.editable) || !empty($champ.private)}
                <dd>
                    {if !empty($champ.private)}
                        (Champ privé)
                    {elseif empty($champ.editable)}
                        (Non-modifiable par les membres)
                    {/if}
                </dd>
                {/if}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset id="f_passe">
        <legend>Connexion</legend>
        <dl>
            <dt><label for="f_passe">Mot de passe</label>{if !empty($champs.passe.mandatory)} <b title="(Champ obligatoire)">obligatoire</b>{/if}</dt>
            <dd><input type="password" id="f_passe" disabled="disabled" /></dd>
            {if empty($champs.passe.editable) || !empty($champs.passe.private)}
            <dd>
                {if !empty($champs.passe.private)}
                    (Champ privé)
                {elseif empty($champs.passe.editable)}
                    (Non-modifiable par les membres)
                {/if}
            </dd>
            {/if}
        </dl>
    </fieldset>

    <form method="post" action="{$admin_url}config/membres.php">
        <p class="submit">
            {csrf_field key="config_membres"}
            <input type="submit" name="back" value="&larr; Retour à l'édition" class="minor" />
            <input type="submit" name="reset" value="Annuler les changements" class="minor" />
            <input type="submit" name="save" value="Enregistrer &rarr;" />
        </p>
    </form>
{else}
    <p class="help">
        Cette page vous permet de personnaliser les fiches d'information des membres de l'association.<br />
        <strong>Attention :</strong> Les champs supprimés de la fiche seront effacés de toutes les fiches de tous les membres, et les données qu'ils contenaient seront perdues.
    </p>

    <fieldset>
        <legend>Champs non-personnalisables</legend>
        <dl>
            <dt>Numéro unique</dt>
            <dd>Ce numéro identifie de manière unique chacun des membres. 
                Il est incrémenté à chaque nouveau membre ajouté.</dd>
            <dt>Catégorie</dt>
            <dd>Identifie la catégorie du membre.</dd>
            <dt>Date de dernière connexion</dt>
            <dd>Mémorise la date de dernière connexion à l'administration de Garradin.</dd>
            <dt>Date d'inscription</dt>
            <dd>Enregistre la date de création de la fiche du membre.</dd>
        </dl>
    </fieldset>

    {if !empty($presets)}
    <form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Ajouter un champ pré-défini</legend>
        <p>
            <select name="preset" required="required">
                <option></option>
                {foreach from=$presets key="name" item="preset"}
                <option value="{$name|escape}">{$name|escape} &mdash; {$preset.title|escape}</option>
                {/foreach}
            </select>
            <input type="hidden" name="{$csrf_name|escape}" value="{$csrf_value|escape}" />
            <input type="submit" name="add" value="Ajouter ce champ à la fiche membre" />
        </p>
    </fieldset>
    </form>
    {/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Ajouter un champ personnalisé</legend>
        <dl>
            <dt><label for="f_name">Nom unique</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">Ne peut comporter que des lettres minuscules et des tirets bas.</dd>
            <dd><input type="text" name="new" id="f_name" value="{form_field name=new}" size="30" required="required" /></dd>
            <dt><label for="f_title">Titre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="new_title" id="f_title" value="{form_field name=new_title}" size="60" required="required" /></dd>
            <dt><label for="f_type">Type de champ</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="new_type" id="f_type" required="required">
                    {foreach from=$types key="type" item="nom"}
                    <option value="{$type|escape}" {form_field name=new_type selected=$type}>{$nom|escape}</option>
                    {/foreach}
                </select>
            </dd>
        </dl>
        <p>
            <input type="hidden" name="{$csrf_name|escape}" value="{$csrf_value|escape}" />
            <input type="submit" name="add" value="Ajouter ce champ à la fiche membre" />
        </p>
    </fieldset>
</form>

<form method="post" action="{$self_url|escape}">
    <div id="orderFields">
        {foreach from=$champs item="champ" key="nom"}
        {if $nom == 'passe'}{continue}{/if}
        <fieldset id="f_{$nom|escape}">
            <legend>{$nom|escape}</legend>
            <dl>
                <dt><label>Type</label></dt>
                <dd>{$champ.type|get_type}</dd>
                <dt><label for="f_{$nom|escape}_title">Titre</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
                <dd><input type="text" name="champs[{$nom|escape}][title]" id="f_{$nom|escape}_title" value="{form_field data=$champs[$nom] name=title}" size="60" required="required" /></dd>
                <dt><label for="f_{$nom|escape}_help">Aide</label></dt>
                <dd><input type="text" name="champs[{$nom|escape}][help]" id="f_{$nom|escape}_help" value="{form_field data=$champs[$nom] name=help}" size="100" /></dd>
                <dt><label><input type="checkbox" name="champs[{$nom|escape}][editable]" value="1" {form_field data=$champs[$nom] name=editable checked="1"} /> Modifiable par les membres</label></dt>
                <dd class="help">Si coché, les membres pourront changer cette information depuis leur espace personnel.</dd>
                <dt><label><input type="checkbox" name="champs[{$nom|escape}][mandatory]" value="1" {form_field data=$champs[$nom] name=mandatory checked="1"} /> Champ obligatoire</label></dt>
                <dd class="help">Si coché, ce champ ne pourra rester vide.</dd>
                <dt><label><input type="checkbox" name="champs[{$nom|escape}][private]" value="1" {form_field data=$champs[$nom] name=private checked="1"} /> Champ privé</label></dt>
                <dd class="help">Si coché, ce champ ne sera visible et modifiable que par les personnes pouvant gérer les membres, mais pas les membres eux-même.</dd>
                {if $champ.type == 'select' || $champ.type == 'multiple'}
                    <dt><label>Options disponibles</label></dt>
                    {if $champ.type == 'multiple'}
                        <dd class="help">Attention changer l'ordre des options peut avoir des effets indésirables.</dd>
                    {else}
                        <dd class="help">Attention renommer ou supprimer une option n'affecte pas ce qui a déjà été enregistré dans les fiches des membres.</dd>
                    {/if}
                    <dd>
                        <{if $champ.type == 'multiple'}ol{else}ul{/if} class="options">
                        {if !empty($champ.options)}
                            {foreach from=$champ.options key="key" item="opt"}
                                <li><input type="text" name="champs[{$nom|escape}][options][]" value="{$opt|escape}" size="50" /></li>
                            {/foreach}
                        {/if}
                        {if $champ.type == 'select' || empty($champ.options) || count($champ.options) < 32}
                            <li><input type="text" name="champs[{$nom|escape}][options][]" value="" size="50" /></li>
                        {/if}
                    </dd>
                {/if}
                <dt><label for="f_{$nom|escape}_list_row">Numéro de colonne dans la liste des membres</label></dt>
                <dd class="help">Laisser vide ou indiquer le chiffre zéro pour que ce champ n'apparaisse pas dans la liste des membres. Inscrire un chiffre entre 1 et 10 pour indiquer l'ordre d'affichage du champ dans le tableau de la liste des membres.</dd>
                <dd><input type="number" id="f_{$nom|escape}_list_row" name="champs[{$nom|escape}][list_row]" min="0" max="10" value="{form_field data=$champs[$nom] name=list_row}" /></dd>
            </dl>
        </fieldset>
        {/foreach}
    </div>

    <fieldset id="f_passe">
        <legend>Mot de passe</legend>
        <dl>
            <dt><label><input type="checkbox" name="champs[passe][editable]" value="1" {form_field data=$champs.passe name=editable checked="1"} /> Modifiable par les membres</label></dt>
            <dd class="help">Si coché, les membres pourront changer cette information depuis leur espace personnel.</dd>
            <dt><label><input type="checkbox" name="champs[passe][mandatory]" value="1" {form_field data=$champs.passe name=mandatory checked="1"} /> Champ obligatoire</label></dt>
            <dd class="help">Si coché, ce champ ne pourra rester vide.</dd>
            <dt><label><input type="checkbox" name="champs[passe][private]" value="1" {form_field data=$champs.passe name=private checked="1"} /> Champ privé</label></dt>
            <dd class="help">Si coché, ce champ ne sera visible et modifiable que par les personnes pouvant gérer les membres, mais pas les membres eux-même.</dd>
        </dl>
    </fieldset>

    <p class="submit">
        <input type="hidden" name="{$csrf_name|escape}" value="{$csrf_value|escape}" />
        <input type="submit" name="reset" value="Annuler les changements" class="minor" />
        <input type="submit" name="review" value="Enregistrer &rarr;" />
        (un récapitulatif sera présenté et une confirmation sera demandée)
    </p>
</form>

<script type="text/javascript">
var champ_identifiant = "f_{$config.champ_identifiant|escape:'js'}";
var champ_identite = "f_{$config.champ_identite|escape:'js'}";

{literal}
(function () {
    if (!document.querySelector || !document.querySelectorAll)
    {
        return false;
    }

    var fields = document.querySelectorAll('#orderFields fieldset');

    for (i = 0; i < fields.length; i++)
    {
        var field = fields[i];
        field.querySelector('dl').style.display = 'none';

        var legend = field.querySelector('legend');

        legend.onclick = function () {
            var content = this.parentNode.querySelector('dl');
            if (content.style.display.toLowerCase() == 'none')
                content.style.display = 'block';
            else
                content.style.display = 'none';
        }

        legend.className = 'interactive';
        legend.title = 'Cliquer pour modifier ce champ';

        var actions = document.createElement('div');
        actions.className = 'actions';
        field.appendChild(actions);

        var up = document.createElement('a');
        up.className = 'icn up';
        up.innerHTML = '&uarr;';
        up.title = 'Déplacer vers le haut';
        up.onclick = function (e) {
            var field = this.parentNode.parentNode;
            var p = field.previousSibling;
            while (p.nodeType == 3) { p = p.previousSibling; }
            field.parentNode.insertBefore(field, p);
            return false;
        };
        actions.appendChild(up);

        var down = document.createElement('a');
        down.className = 'icn down';
        down.innerHTML = '&darr;';
        down.title = 'Déplacer vers le bas';
        down.onclick = function (e) {
            var field = this.parentNode.parentNode;
            var p = field.nextSibling;

            if (!p.nextSibling)
            {
                field.parentNode.appendChild(field);
            }
            else
            {
                while (p.nodeType == 3) { p = p.nextSibling; }
                p = p.nextSibling;
                while (p.nodeType == 3) { p = p.nextSibling; }
                field.parentNode.insertBefore(field, p);
            }
            return false;
        };
        actions.appendChild(down);

        var edit = document.createElement('a');
        edit.className = 'icn edit';
        edit.innerHTML = '&#x270e;';
        edit.title = 'Modifier ce champ';
        edit.onclick = function (e) {
            var content = this.parentNode.parentNode.querySelector('dl');
            if (content.style.display.toLowerCase() == 'none')
                content.style.display = 'block';
            else
                content.style.display = 'none';
            return false;
        };
        actions.appendChild(edit);

        if (field.id != champ_identifiant && field.id != 'f_passe' && field.id != champ_identite)
        {
            var rem = document.createElement('a');
            rem.className = 'icn remove';
            rem.innerHTML = '✘';
            rem.title = 'Enlever ce champ de la fiche';
            rem.onclick = function (e) {
                if (!window.confirm('Êtes-vous sûr de supprimer ce champ des fiches de membre ?'))
                {
                    return false;
                }

                var field = this.parentNode.parentNode;
                field.parentNode.removeChild(field);
                return false;
            };
            actions.appendChild(rem);
        }

        if (field.querySelector('.options'))
        {
            var options = field.querySelectorAll('.options li');
            var options_nb = options.length;

            if (options[0].parentNode.tagName.toLowerCase() == 'ul')
            {
                // champ select
                for (j = 0; j < options_nb; j++)
                {
                    var remove = document.createElement('input');
                    remove.type = 'button';
                    remove.className = 'icn';
                    remove.value = '-';
                    remove.title = 'Enlever cette option';
                    remove.onclick = function (e) {
                        var p = this.parentNode;
                        p.parentNode.removeChild(p);
                    };
                    options[j].appendChild(remove);
                }
            }

            var add = document.createElement('input');
            add.type = 'button';
            add.className = 'icn add';
            add.value = '+';
            add.title = 'Ajouter une option';
            add.onclick = function (e) {
                var p = this.parentNode.parentNode;
                var options = p.querySelectorAll('li');
                var new_option = this.parentNode.cloneNode(true);
                var btn = new_option.querySelector('input.add');
                new_option.getElementsByTagName('input')[0].value = '';

                if (options.length >= 30)
                {
                    new_option.removeChild(btn);
                }
                else
                {
                    btn.onclick = this.onclick;
                }

                p.appendChild(new_option);
                this.parentNode.removeChild(this);
            };

            options[options_nb - 1].appendChild(add);
        }
    }
}());
{/literal}
</script>
{/if}

{include file="admin/_foot.tpl"}