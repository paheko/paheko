<?php
use Garradin\Entities\Accounting\Chart;
use Garradin\Config;

$country_list = Chart::COUNTRY_LIST + ['' => '— Autre'];

if (!isset($chart)) {
	$chart = new Chart;
	$chart->country = Config::getInstance()->pays;
}

$name ??= 'country';
?>

{input type="select" name=$name label="Appliquer les règles comptables de ce pays" required=1 options=$country_list default=$chart.country}

<dd class="alert block {$name}_empty hidden"><strong>Attention&nbsp;:</strong> si <em>«&nbsp;Autre&nbsp;»</em> est sélectionné, alors&nbsp;:<br />
	- des erreurs de position au bilan ou au résultat seront possibles<br />
	- il ne sera pas possible d'utiliser les comptes usuels
</dd>

<dd class="help">

<script type="text/javascript">
(function () {ldelim}
	var n = {$name|escape:'json'};
	var c = $('#f_' + n);
	{literal}
	var changeCountry = () => {
		g.toggle('.' + n + '_empty', c.value == '' ? true : false);
		g.resizeParentDialog();
	};

	c.onchange = changeCountry;
	changeCountry();
})();
{/literal}
</script>
