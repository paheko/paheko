<?php

namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Web\Web;
use Paheko\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$list = Web::getSitemap();

$can_edit = $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$tpl->assign(compact('list', 'can_edit'));

function display_sitemap($list, $level = 3): string
{
	$out = '<ul>';
	foreach ($list as $item) {
		$out .= sprintf('<li><h%d class="status-%s"><a href="./?id=%d">%s</a></%1$d>', min($level, 6), $item->inherited_status, $item->id, htmlspecialchars($item->title));

		if (!empty($item->children)) {
			$out .= display_sitemap($item->children, $level + 1);
		}

		$out .= '</li>';
	}
	$out .= '</ul>';
	return $out;
}

$tpl->register_modifier('display_sitemap', 'Paheko\display_sitemap');

$tpl->display('web/sitemap.tpl');
