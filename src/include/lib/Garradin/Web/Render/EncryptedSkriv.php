<?php

namespace Garradin\Web\Render;

use Garradin\Entities\Files\File;
use Garradin\Template;

class EncryptedSkriv
{
	static public function render(File $file, ?string $content = null): string
	{
		$tpl = Template::getInstance();
		$content = $content ?? $file->fetch();
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/_file_render_encrypted.tpl');
	}
}
