<?php
use Garradin\Users\Session;
use Garradin\Users\DynamicFields;

$field = current(DynamicFields::getInstance()->fieldsBySystemUse('password'));
$password_length = Session::MINIMUM_PASSWORD_LENGTH;
$suggestion = Utils::suggestPassword();
$required = $required ?? $field->required;
?>

<dd class="help">
	Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
</dd>
<dd class="help">
	Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
	<input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$suggestion}" autocomplete="off" />
</dd>

{input type="password" name="password" required=$required label="Mot de passe" help="Minimum %d caractères"|args:$password_length autocomplete="off" minlength=$password_length}

{input type="password" name="password_confirmed" required=$required label="Encore le mot de pase (vérification)" help="Minimum %d caractères"|args:$password_length autocomplete="off" minlength=$password_length}

<script type="text/javascript" async="async">
{literal}
g.script('scripts/password.js', () => {
	initPasswordField('pw_suggest', 'f_password', 'f_password_confirmed');
});
{/literal}
</script>