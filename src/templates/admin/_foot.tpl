</main>

<script type="text/javascript" defer="defer">
var keep_session_url = "{$www_url}admin/login.php?keepSessionAlive&";
{literal}
(function () {
    function refreshSession()
    {
        var i = new Image(1, 1);
        var d = +new Date;
        i.src = keep_session_url + d;
    }

    window.setInterval(refreshSession, 10 * 60 * 1000);
} ());
{/literal}
</script>

</body>
</html>
