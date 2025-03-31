@start "" http://127.0.0.1:8082/

@echo =================================================
@echo.
@echo  Demarrage du serveur PHP de Paheko.
@echo.
@echo  Paheko est disponible a l'adresse suivante :
@echo  http://127.0.0.1:8082/
@echo.
@echo  Fermer cette fenetre pour arreter le serveur.
@echo.
@echo =================================================
@echo.

REM PHP server on Windows doesn't support multiple workers
REM see https://github.com/php/php-src/issues/12071
php\php.exe -S 127.0.0.1:8082 -t paheko/www paheko/www/_route.php 2> NUL
