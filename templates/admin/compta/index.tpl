{include file="admin/_head.tpl" title="Comptabilité" current="compta"}

<p class="alert">
    <strong>Attention !</strong>
    La comptabilité est une fonctionnalité en beta,
    il est déconseillé pour le moment de l'utiliser pour la
    comptabilité réelle de votre association.<br />
    Vous êtes cependant encouragé à la tester et à faire part
    de votre retour sur le site de <a href="http://dev.kd2.org/garradin/">Garradin</a>.
</p>

<ul class="actions">
    <li class="gestion"><a href="{$www_url}admin/compta/gestion.php">Suivi des opérations</a></li>
    <li class="journal"><a href="{$www_url}admin/compta/rapport/journal.php">Journal général</a></li>
    <li class="grand_livre"><a href="{$www_url}admin/compta/rapport/grand_livre.php">Grand livre</a></li>
    <li class="compte_resultat"><a href="{$www_url}admin/compta/rapport/compte_resultat.php">Compte de résultat</a></li>
    <li class="bilan"><a href="{$www_url}admin/compta/rapport/bilan.php">Bilan</a></li>
</ul>

<h3>Évolution des recettes et dépenses sur les 30 derniers jours</h3>
<p>
    <img src="{$www_url}admin/compta/graph.php?g=recettes_depenses" />
</p>

<h3>Évolution actif/passif sur les 30 derniers jours</h3>
<p>
    <img src="{$www_url}admin/compta/graph.php?g=actif_passif" />
</p>

{include file="admin/_foot.tpl"}