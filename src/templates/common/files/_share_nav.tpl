<nav class="tabs">
	<ul>
		<li {if $current !== 'list'} class="current"{/if}>{link href="share.php?h=%s"|args:$file.hash_id label="Nouveau partage"}</li>
		<li {if $current === 'list'} class="current"{/if}>{link href="shares_list.php?h=%s"|args:$file.hash_id label="Partages existants"}</li>
	</ul>
</nav>
