Set wShell = CreateObject("Wscript.Shell")

Dim args

' Kill any PHP process that we started before
args = "taskkill /IM php.exe /F"
wShell.Run args, 0, true

' PHP server on Windows doesn't support multiple workers
' see https://github.com/php/php-src/issues/12071
args = "php\php.exe -S 127.0.0.1:8082 -t paheko/www paheko/www/_route.php 2> NUL"
wShell.Run args, 0, false

' Open web browser
wShell.run "http://127.0.0.1:8082/", 3, true
