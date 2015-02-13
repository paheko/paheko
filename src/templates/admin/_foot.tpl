</div>

<script type="text/javascript" defer="defer">
{literal}
(function () {
    var keep_session_url = "{/literal}{$www_url}{literal}admin/login.php?keepSessionAlive&";

    function refreshSession()
    {
        var _RIMAGE = new Image(1,1);
        _RIMAGE.src = keep_session_url + Math.round(Math.random()*1000000000);
    }
    window.setInterval(refreshSession, 10 * 60 * 1000);
} ());
{/literal}
</script>

</body>
</html>
