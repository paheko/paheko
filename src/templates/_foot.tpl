</main>

{if $is_logged}
{* Keep session alive by requesting renewal every before it expires *}
<script type="text/javascript" defer="defer">
(function () {ldelim}
	var keep_session_url = "{$admin_url}login.php?keepSessionAlive&";
	var session_gc = <?=intval(ini_get('session.gc_maxlifetime'))?>;

	window.setInterval(
		() => fetch(g.admin_url + 'login.php?keepSessionAlive&' + (+new Date)),
		(session_gc - 5*60)*1000
	);

	{if !LOCAL_LOGIN && $config.auto_logout && !$session->hasRememberMeCookie()}
		g.auto_logout = {$config.auto_logout};
		g.script('scripts/auto_logout.js');
	{/if}
{rdelim})();
</script>
{/if}

</body>
</html>
