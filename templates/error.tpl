<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Erreur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style type="text/css">
    {literal}
    * { margin: 0; padding: 0; }

    html { width: 100%; height: 100%; }
    body {
        font-size: 100%;
        color: #000;
        font-family: "Trebuchet MS", Helvetica, Sans-serif;
        background: #fff;
        padding: 1em;
    }

    h1 { color: #9c4f15; margin-bottom: 10px; }

    p.error {
        border: 1px solid #c00;
        background: #fcc;
        padding: 0.5em;
        margin-bottom: 1em;
    }
    {/literal}
    </style>
</head>

<body>

<h1>Erreur</h1>

<p class="error">
    {$error|escape}
</p>

<p>
    <a href="{$www_url}" onclick="history.back(); return false;">&larr; Retour</a>
</p>

</body>
</html>