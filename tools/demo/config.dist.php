<?php

namespace Paheko;

// Copy this file and rename it to config.local.php in this same directory

// Domain of the website exposing this demo, expecting this to be on demo.example.org, and demo-XXXX.example.org
const DEMO_PARENT_DOMAIN = 'example.org';

// Where each demo account will be stored
const DEMO_STORAGE_PATH = __DIR__ . '/../data/%s';

// List of example organizations, each is a ".sqlite" file
const EXAMPLE_ORGANIZATIONS = [
	"L'asso du coin" => __DIR__ . '/../examples/asso.sqlite',
	"L'atelier vélo" => __DIR__ . '/../examples/bike.sqlite',
];

const MAIL_ERRORS = 'root@example.org';

const SECRET_KEY = 'XXXXX';

// 10 MB
const FILE_STORAGE_QUOTA = 1024*1024*10;

#const HTTP_LOG_FILE = DATA_ROOT . '/http.log';

const PDF_COMMAND = 'prince';

const CONVERSION_TOOLS = ['ssconvert', 'mupdf', 'collabora'];

const WOPI_DISCOVERY_URL = null;

const PLUGINS_BLOCKLIST = ['invoice', 'dompdf', 'facturx'];

