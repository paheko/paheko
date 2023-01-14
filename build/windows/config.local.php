<?php

namespace Garradin;

if (!empty(getenv('LOCALAPPDATA'))) {
	// Store data in user AppData directory
	define('Garradin\DATA_ROOT', trim(getenv('LOCALAPPDATA'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Paheko');
}

// Always log in as admin user
const LOCAL_LOGIN = -1;
