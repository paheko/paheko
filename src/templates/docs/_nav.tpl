<?php
use Paheko\Entities\Files\File;
?>
<ul>
	{if $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_READ)}
		<li{if $context == File::CONTEXT_DOCUMENTS} class="current"{/if}><a href="./"><strong>Documents</strong></a></li>
	{/if}
	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
		<li{if $context == File::CONTEXT_TRANSACTION} class="current"{/if}><a href="./?path=<?=File::CONTEXT_TRANSACTION?>">{icon shape="money"} Fichiers des écritures</a></li>
	{/if}
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)}
		<li{if $context == File::CONTEXT_USER} class="current"{/if}><a href="./?path=<?=File::CONTEXT_USER?>">{icon shape="users"} Fichiers des membres</a></li>
	{/if}
	{if $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_ADMIN)
		|| $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
		<li{if $context == 'shares'} class="current"{/if}><a href="shares.php">{icon shape="export"} Partages</a></li>
		<li{if $context == 'trash'} class="current"{/if}><a href="trash.php">{icon shape="trash"} Fichiers supprimés</a></li>
	{/if}
</ul>