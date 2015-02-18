{include file="admin/_head.tpl" title="Rapprochement — `$compte.id`" current="compta/banques" js=1}

<ul class="actions">
    <li><a href="{$www_url}admin/compta/banques/">Comptes bancaires</a></li>
    <li><a href="{$www_url}admin/compta/comptes/journal.php?id={Garradin\Compta\Comptes::CAISSE}&amp;suivi">Journal de caisse</a></li>
    <li class="current"><a href="{$admin_url}compta/banques/rapprochement.php?id={$compte.id|escape}">Rapprochement</a></li>
</ul>


{include file="admin/_foot.tpl"}