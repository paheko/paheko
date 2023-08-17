<?php

namespace Paheko\Web\Render;

use Paheko\Entities\Files\File;
use Paheko\Template;
use const Paheko\ADMIN_URL;

class EncryptedSkriv extends AbstractRender
{
	public function render(string $content): string
	{
		$tpl = Template::getInstance();
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/files/_file_render_encrypted.tpl');
	}
}
