<nav class="tabs">
	{if $current === 'rejected'}
		<aside>
			{exportmenu}
		</aside>
	{elseif $current === 'index'}
		<aside>
			{linkbutton shape="plus" label="Nouveau message" href="new.php" target="_dialog"}
		</aside>
	{/if}

	<ul>
		<li{if $current === 'index'} class="current"{/if}><a href="./">Messages collectifs</a></li>
		<li{if $current === 'optout'} class="current"{/if}><a href="optout.php">Désinscriptions</a></li>
		<li{if $current === 'rejected'} class="current"{/if}><a href="rejected.php">Adresses rejetées</a></li>
	</ul>
</nav>
