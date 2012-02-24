{include file="admin/_head.tpl" title="Envoyer un message collectif" current="membres/message_collectif"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}" onsubmit="return confirm('Envoyer vraiment ce message collectif ?');">
    <fieldset class="memberMessage">
        <legend>Message</legend>
        <dl>
            <dt>Expéditeur</dt>
            <dd>{$config.nom_asso|escape} &lt;{$config.email_asso|escape}&gt;</dd>
            <dt><label for="f_dest">Membres destinataires</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="dest">
                    <option value="0">Toutes les catégories qui ne sont pas cachées</option>
                {foreach from=$cats_liste key="id" item="nom"}
                    <option value="{$id|escape}">{$nom|escape} {if array_key_exists($id, $cats_cachees)}(cachée){/if}</option>
                {/foreach}
                </select>
            </dd>
            <dt><label for="f_sujet">Sujet</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet}" /></dd>
            <dt><label for="f_message">Message</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><textarea name="message" id="f_message" cols="70" rows="25">{form_field name=message}</textarea></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="send_message_collectif"}
        <input type="submit" name="save" value="Envoyer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}