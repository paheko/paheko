</div>

<script type="text/javascript" defer="defer">
{literal}
(function () {
    var keep_session_url = "{$www_url}admin/login.php?keepSessionAlive&amp;";

    function refreshSession()
    {
        var _RIMAGE = new Image(1,1);
        _RIMAGE.src = keep_session_url + Math.round(Math.random()*1000000000);
        window.setTimeout("refreshSession()", 15 * 60 * 1000);
    }
    window.setTimeout(refreshSession, 20 * 60 * 1000);
} ());
{/literal}
</script>

</body>
</html>