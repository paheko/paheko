<?php

namespace Garradin\Entities\Documents;

use Garradin\Entity;
use Garradin\ValidationException;

class Template extends Entity
{
	const TABLE = 'doc_templates';

	protected $id;
	protected $label;
	protected $description;
	protected $content;

	protected $_types = [
		'id'          => 'int',
		'label'       => 'string',
		'description' => '?string',
		'content'     => 'string',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
	}

	public function render(array $fields): string
	{
		$mu = new \KD2\Mustachier(null, CACHE_ROOT);
		$db = DB::getInstance();
		$content = $this->content;

		static $config_keys = ['nom_asso', 'adresse_asso', 'email_asso'];
		$config = Config::getInstance();

		foreach ($config_keys as $key) {
			$mu->assign($key, $config->get($key));
		}

		preg_match_all('!\{\{sql\s+(\w+)=(.*)\}\}!ism', $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$sql = $match[2];
			$mu->assign($match[1], $db->iterate($sql));
			$content = str_replace($match[0], '', $content);
		}

		foreach ($fields as $key => $value) {
			$mu->assign($key, $value);
		}

		return $mu->render($content, [], true);
	}
}
