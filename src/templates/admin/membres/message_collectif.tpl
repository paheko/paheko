{include file="admin/_head.tpl" title="Envoyer un message collectif" current="membres/message_collectif"}

{form_errors}

<form method="post" action="{$self_url}" onsubmit="return confirm('Envoyer vraiment ce message collectif ?');">
    <fieldset class="memberMessage">
        <legend>Message</legend>
        <dl>
            <dt>Expéditeur</dt>
            <dd>{$config.nom_asso} &lt;{$config.email_asso}&gt;</dd>
            <dt><label for="f_dest">Membres destinataires</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="dest">
                    <option value="0">Toutes les catégories qui ne sont pas cachées</option>
                {foreach from=$cats_liste key="id" item="nom"}
                    <option value="{$id}">{$nom} {if array_key_exists($id, $cats_cachees)}[cachée]{/if}</option>
                {/foreach}
                </select>
            </dd>
            <dd>
                <input type="checkbox" id="f_subscribed" name="subscribed" value="1" {form_field name="subscribed" default="1" checked="1"} />
                <label for="f_subscribed">Seulement les membres inscrits à la lettre d'information</label>
            </dd>
            <dt><label for="f_sujet">Sujet</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd class="help">Sera automatiquement précédé de la mention [{$config.nom_asso}]</dd>
            <dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet}" required="required" /></dd>
            <dt><label for="f_message">Message</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><textarea name="message" id="f_message" cols="72" rows="25" required="required">{form_field name=message}</textarea></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="send_message_collectif"}
        <input type="submit" name="save" value="Envoyer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}