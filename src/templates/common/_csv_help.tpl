<dd class="help block">
	Merci de respecter les règles suivantes&nbsp;:
	<ul>
	{if !$csv->canConvert()}
		<li>Il est recommandé d'utiliser LibreOffice pour créer le fichier CSV, Excel est très mauvais pour ça.</li>
		<li>Le fichier doit être en UTF-8</li>
		<li>Le séparateur doit être le point-virgule ou la virgule</li>
		<li>Cocher l'option <em>"Mettre en guillemets toutes les cellules du texte"</em></li>
	{/if}
		<li>Le fichier peut comporter les colonnes suivantes : <em>{$csv->getColumnsString()}</em>.</li>
		{if ($columns = $csv->getMandatoryColumnsString())}<li>Le fichier <strong>doit obligatoirement</strong> comporter les colonnes suivantes : <em>{$columns}</em></li>{/if}
	{if isset($more_text)}
		<?php $more = explode("\n", trim($more_text)); ?>
		{foreach from=$more item="text"}
			<li>{$text}</li>
		{/foreach}
	{/if}
	</ul>
</dd>