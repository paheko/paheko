<?php $can_convert = $csv->canConvert(); ?>
<dd class="help block">
	<h4>Règles à respecter pour les {if $can_convert}fichiers CSV, LibreOffice et Excel{else}CSV{/if}&nbsp;:</h4>
	<ul>
	{if !$can_convert}
		<li>Il est recommandé d'utiliser LibreOffice pour créer le fichier CSV (Excel est nul).</li>
		<li>Le fichier doit être en UTF-8</li>
		<li>Le séparateur doit être le point-virgule ou la virgule</li>
		<li>Cocher l'option <em>"Mettre en guillemets toutes les cellules du texte"</em></li>
	{/if}
		<li>Le fichier peut comporter les colonnes suivantes : <em>{$csv->getColumnsString()}</em>.</li>
		{if ($columns = $csv->getMandatoryColumnsString())}
			<li>Le fichier <strong>doit obligatoirement</strong> comporter les colonnes suivantes : <em>{$columns}</em></li>
		{/if}
		{if isset($more_text)}
			<?php $more = explode("\n", trim($more_text)); ?>
			{foreach from=$more item="text"}
				<li>{$text}</li>
			{/foreach}
		{/if}
	</ul>
</dd>