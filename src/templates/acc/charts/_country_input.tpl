<?php
use Paheko\Entities\Accounting\Chart;
use Paheko\Config;

$country_list = Chart::COUNTRY_LIST + ['' => '— Autre'];

if (!isset($chart)) {
	$chart = new Chart;
	$chart->country = Config::getInstance()->pays;
}

$name ??= 'country';
?>

{input type="select" name=$name label="Appliquer les règles comptables de ce pays" required=1 options=$country_list default=$chart.country}

{if !$chart->exists()}
<dd class="help">Ce choix ne pourra plus être modifié une fois le plan comptable créé.</dd>
{else}
<dd class="help">Si un pays est sélectionné, ce choix ne pourra plus être modifié.</dd>
{/if}

<dd class="alert block {$name}_empty hidden"><strong>Attention&nbsp;:</strong> si <em>«&nbsp;Autre&nbsp;»</em> est sélectionné, alors&nbsp;:<br />
	- les comptes ne pourront pas être catégorisés automatiquement (banque, caisse, dépenses, recettes, etc.)&nbsp;;<br />
	- il faudra donc parcourir tout le plan comptable pour sélectionner un compte<br />
	- la position des comptes au bilan ou compte de résultat ne pourra pas être contrôlée : des erreurs sont possibles<br />
	<em>Si vous avez besoin d'ajouter les règles comptables d'un autre pays, merci de <a href="https://paheko.cloud/contact" target="_blank">nous contacter</a>.</em>
</dd>

<dd class="help">

<script type="text/javascript">
(function () {ldelim}
	var n = {$name|escape:'json'};
	var c = $('#f_' + n);
	{literal}
	var changeCountry = () => {
		g.toggle('.' + n + '_empty', c.value == '' ? true : false);
	};

	c.onchange = changeCountry;
	changeCountry();
})();
{/literal}
</script>

</dd>