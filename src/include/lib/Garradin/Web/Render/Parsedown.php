<?php

namespace Garradin\Web\Render;

//use Parsedown;
//use ParsedownExtra;
use ParsedownToc;

use Garradin\Entities\Files\File;

class Parsedown extends ParsedownToc
{
	protected $skriv;

	function __construct(?File $file)
	{
		$this->BlockTypes['<'][] = 'SkrivExtension';
		parent::__construct();
		$this->skriv = new Skriv;

		if ($file) {
			$this->skriv->isRelativeTo($file);
		}
	}

	function blockSkrivExtension(array $line, array $block): ?array
	{
		$line = $line['text'];

		if (strpos($line, '<<') === 0 && preg_match('/^<<<?([a-z_]+)((?:(?!>>>?).)*?)(>>>?$|$)/i', trim($line), $match)) {
			$text = $this->skriv->callExtension($match);

			return [
				'char' => $line[0],
				'element' => array(
					'name' => 'div',
					'rawHtml' => $text,
					'allowRawHtmlInSafeMode' => true,
				),
				'complete' => true,
			];
		}

		return null;
	}

	protected function blockSkrivExtensionComplete(array $block): ?array
	{
		var_dump($block);
		return $block;
	}
}