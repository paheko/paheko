<?php

namespace Garradin\Files\Render;

class EncryptedSkriv
{
	static public function render(File $file, string $content): string
	{
		$tpl = Template::getInstance();
		$tpl->assign(compact('file', 'content'));
		return $tpl->fetch('common/_file_render_encrypted.tpl');
	}
}
