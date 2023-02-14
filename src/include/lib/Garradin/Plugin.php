<?php

namespace Garradin;

use Garradin\Users\Session;

class Plugin
{


	static public function getURL(string $id, string $path = '')
	{
		return ADMIN_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}

	static public function getPublicURL(string $id, string $path = '')
	{
		return WWW_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}



}