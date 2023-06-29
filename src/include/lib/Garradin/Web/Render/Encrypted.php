<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;
use Garradin\Template;
use const Garradin\ADMIN_URL;
use const Garradin\WWW_URL;

class Encrypted extends AbstractRender
{
	public function render(?string $content = null): string
	{
		$tpl = Template::getInstance();
		$file = $this->file;
		$content = $content ?? $file->fetch();
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->assign('url', WWW_URL . $this->current_path);
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/files/_file_render_encrypted.tpl');
	}
}