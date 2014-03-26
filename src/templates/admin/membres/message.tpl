{include file="admin/_head.tpl" title="Contacter un membre" current="membres"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset class="memberMessage">
        <legend>Message</legend>
        <dl>
            <dt>Expéditeur</dt>
            <dd>{$user.identite|escape} &lt;{$user.email|escape}&gt;</dd>
            <dd class="help">
                Votre adresse E-Mail apparaîtra dans le champ "expéditeur" du message reçu par le destinataire.
            </dd>
            <dt>Destinataire</dt>
            <dd>{$membre.identite|escape} ({$categorie.nom|escape})</dd>
            <dt><label for="f_sujet">Sujet</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="sujet" id="f_sujet" value="{form_field name=sujet}" required="required" /></dd>
            <dt><label for="f_message">Message</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><textarea name="message" id="f_message" cols="72" rows="25" required="required">{form_field name=message}</textarea></dd>
            <dd>
                <input type="checkbox" name="copie" id="f_copie" value="1" />
                <label for="f_copie">Recevoir par e-mail une copie du message envoyé</label>
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="send_message_"|cat:$membre.id}
        <input type="submit" name="save" value="Envoyer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}