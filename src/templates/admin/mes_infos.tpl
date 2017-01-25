{include file="admin/_head.tpl" title="Mes informations personnelles" current="mes_infos" js=1}

{if $error}
    <p class="error">
        {$error}
    </p>
{elseif $otp_status == 'off'}
    <p class="confirm">
        L'authentification à double facteur a été désactivée.
    </p>
{elseif $otp_status}
    <div class="alert">
        <img class="qrcode" src="{$otp_qrcode}" alt="" />
        <p class="confirm">L'authentification à double facteur a été activée.</p>
        <p class="help">
            Votre clé secrète est&nbsp;:<br />
            <code>{$otp_status}</code><br />
            Recopiez-la ou scannez le QR code pour configurer votre application.
        </p>
    </div>
{/if}

<form method="post" action="{$self_url}">


    <fieldset>
        <legend>Informations personnelles</legend>
        <dl>
            {foreach from=$champs item="champ" key="nom"}
            {if empty($champ.private) && $nom != 'passe'}
                {html_champ_membre config=$champ name=$nom data=$membre user_mode=true}
            {/if}
            {/foreach}
        </dl>
    </fieldset>

    <fieldset>
        <legend>Changer mon mot de passe</legend>
        {if $user.droits.membres < Garradin\Membres::DROIT_ADMIN && (!empty($champs.passe.private) || empty($champs.passe.editable))}
            <p class="help">Vous devez contacter un administrateur pour changer votre mot de passe.</p>
        {else}
            <dl>
                <dd>Vous avez déjà un mot de passe, ne remplissez les champs suivants que si vous souhaitez en changer.</dd>
                <dt><label for="f_passe">Nouveau mot de passe</label></dt>
                <dd class="help">
                    Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
                    et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
                </dd>
                <dd class="help">
                    Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
                    <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="password_suggest" value="{$passphrase}" />
                </dd>
                <dd><input type="password" name="passe" id="f_passe" value="{form_field name=passe}" pattern=".{ldelim}5,{rdelim}" /></dd>
                <dt><label for="f_repasse">Encore le mot de passe</label> (vérification)</dt>
                <dd><input type="password" name="repasse" id="f_repasse" value="{form_field name=repasse}" pattern=".{ldelim}5,{rdelim}" /></dd>
            </dl>
        {/if}
    </fieldset>

    <fieldset>
        <legend>Authentification à double facteur (2FA)</legend>
        <p class="help">Pour renforcer la sécurité de votre connexion en cas de vol de votre mot de passe, vous pouvez activer
            l'authentification à double facteur. Cela nécessite d'installer une application comme <a href="https://freeotp.github.io/">FreeOTP</a>
            sur votre téléphone.</p>
        <dl>
            <dt>Authentification à double facteur (TOTP)</dt>
        {if $user.secret_otp}
            <dd><label><input type="radio" name="otp" value="" checked="checked" /> <strong>Activée</strong></label></dd>
            <dd><label><input type="radio" name="otp" value="generate" /> Régénérer une nouvelle clé secrète</label></dd>
            <dd><label><input type="radio" name="otp" value="disable" /> Désactiver l'authentification à double facteur</label></dd>
        {else}
            <dd><em>Désactivée</em></dd>
            <dd><label><input type="checkbox" name="otp" value="generate" /> Activer</label></dd>
        {/if}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="edit_me"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>


<script type="text/javascript">
{literal}
g.script('scripts/password.js').onload = function () {
    initPasswordField('password_suggest', 'f_passe', 'f_repasse');
};
{/literal}
</script>

{include file="admin/_foot.tpl"}