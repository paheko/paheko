# Paheko demo

This directory hosts the code used on <https://demo.paheko.cloud/>.

It basically downloads Paheko source code and offers users with the creation of test accounts for limited time.

This is offered without any support, just in case someone finds it useful.

It is **NOT RECOMMENDED** to use this set of scripts as a basis for a hosting service!

# Configuration

Make sure you create a separate system user for this, as the demo can be insecure.

Always replace `.../` with the path where you have installed this demo.

## Initial setup

1. Run `make clone` 
2. Copy `config.dist.php` to `config.local.php` to suit your needs
3. Run `make update`

## Cron job

Deleting the old demo accounts is left to this cron job:

```
0 2 * * *		cd .../demo/data && find . -mindepth 1 -maxdepth 1 -type d -mtime +7 | xargs rm -rf
```

You could also set up a cron job to update the source code:

```
@daily cd .../demo && make update
```

## Apache configuration

Very simple virtual host.

```
<VirtualHost *:443>
    ServerName demo.example.org
    ServerAlias demo-*.example.org

    DocumentRoot .../demo/src/paheko/src/www

    DirectoryIndex index.php

    SSLEngine On
    SSLCertificateFile .../conf/certs/certs/demo.example.org/fullchain.pem
    SSLCertificateKeyFile .../conf/certs/certs/demo.example.org/privkey.pem
</VirtualHost>

<Directory .../demo/paheko/src/www>
    Require all granted
    DirectoryIndex index.php index.html
    FallbackResource /_route.php
</Directory>
```
