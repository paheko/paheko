<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;
use Garradin\Template;
use const Garradin\ADMIN_URL;

class EncryptedSkriv
{
	public function render(File $file, ?string $content = null): string
	{
		$tpl = Template::getInstance();
		$content = $content ?? $file->fetch();
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/files/_file_render_encrypted.tpl');
	}
}
