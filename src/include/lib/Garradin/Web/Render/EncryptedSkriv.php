<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;
use Garradin\Template;
use const Garradin\ADMIN_URL;

class EncryptedSkriv extends AbstractRender
{
	public function render(?string $content = null, array $options = []): string
	{
		$tpl = Template::getInstance();
		$file = $this->file;
		$content = $content ?? $file->fetch();
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/files/_file_render_encrypted.tpl');
	}
}
