{include file="admin/_head.tpl" title="Contacter un membre" current="membres"}

{form_errors}

<form method="post" action="{$self_url}">
    <fieldset class="memberMessage">
        <legend>Message</legend>
        <dl>
            <dt>Expéditeur</dt>
            <dd>{$user.identite} &lt;{$user.email}&gt;</dd>
            <dd class="help">
                Votre adresse E-Mail apparaîtra dans le champ "expéditeur" du message reçu par le destinataire.
            </dd>
            <dt>Destinataire</dt>
            <dd>{$membre.identite} ({$categorie.nom})</dd>
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