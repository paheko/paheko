<?php

namespace Paheko;

use KD2\Form;
use KD2\HTTP;
use KD2\Smartyer;
use KD2\Translate;
use Paheko\Users\Session;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Users\Category;
use Paheko\Entities\Users\User;
use Paheko\UserTemplate\CommonModifiers;
use Paheko\UserTemplate\CommonFunctions;
use Paheko\Files\Files;

class Template extends Smartyer
{
	static protected $_instance = null;

	static public function getInstance()
	{
		return self::$_instance ?: self::$_instance = new Template;
	}

	public function display($template = null)
	{
		$session = Session::getInstance();
		$this->assign('table_export', false);
		$this->assign('pdf_export', false);

		if ($session->isLogged(true)) {
			if (isset($_GET['_pdf'])) {
				$this->assign('pdf_export', true);
				return $this->PDF($template);
			}
			elseif (isset($_GET['_export']) && $_GET['_export'] == 'test') {
				$this->assign('table_export', true);
			}
			elseif (isset($_GET['_export'])) {
				$this->assign('table_export', true);
				$html = $this->fetch($template);

				if (!stripos($html, '<table')) {
					throw new UserException('Nothing to export: no table found');
				}

				$title = 'Export';

				if (preg_match('!<title>([^<]+)</title>!', $html, $match)) {
					$title = html_entity_decode(trim($match[1]));
				}

				return CSV::exportHTML($_GET['_export'], $html, $title);
			}
		}

		return parent::display($template);
	}

	public function PDF(?string $template = null, ?string $title = null)
	{
		$out = $this->fetch($template);

		if (!$title && preg_match('!<title>(.*)</title>!U', $out, $match)) {
			$title = trim($match[1]);
		}

		header('Content-type: application/pdf');
		header(sprintf('Content-Disposition: attachment; filename="%s.pdf"', Utils::safeFileName($title ?: 'Page')));
		Utils::streamPDF($out);
		return $this;
	}

	private function __clone()
	{
	}

	public function __construct($template = null, Template &$parent = null)
	{
		parent::__construct($template, $parent);

		if (null === $parent) {
			if (self::$_instance !== null) {
				throw new \LogicException('Instance already exists');
			}
		}
		// For included templates just return a new instance,
		// the singleton is only to get the 'master' Template object
		else {
			return $this;
		}

		Translate::extendSmartyer($this);

		$cache_dir = SMARTYER_CACHE_ROOT;

		if (!file_exists($cache_dir)) {
			Utils::safe_mkdir($cache_dir, 0777, true);
		}

		$this->setTemplatesDir(ROOT . '/templates');
		$this->setCompiledDir($cache_dir);
		$this->setNamespace('Paheko');

		$this->assign('version_hash', Utils::getVersionHash());

		$this->assign('www_url', WWW_URL);
		$this->assign('admin_url', ADMIN_URL);
		$this->assign('base_url', BASE_URL);
		$this->assign('help_pattern_url', HELP_PATTERN_URL);
		$this->assign('help_url', sprintf(HELP_URL, str_replace('/admin/', '', Utils::getSelfURI(false))));
		$this->assign('self_url', Utils::getSelfURI());
		$this->assign('self_url_no_qs', Utils::getSelfURI(false));

		$session = null;

		if (!defined('Paheko\INSTALL_PROCESS')) {
			$session = Session::getInstance();
			$this->assign('config', Config::getInstance());
		}
		else {
			$this->assign('config', null);
		}

		$is_logged = $session ? $session->isLogged() : null;

		$this->assign('session', $session);
		$this->assign('is_logged', $is_logged);
		$this->assign('logged_user', $is_logged ? $session->getUser() : null);

		$this->assign('dialog', isset($_GET['_dialog']) ? ($_GET['_dialog'] ?: true) : false);

		$this->register_compile_function('continue', function (Smartyer $s, $pos, $block, $name, $raw_args) {
			if ($block == 'continue')
			{
				return 'continue;';
			}
		});

		$this->register_compile_function('use', function (Smartyer $s, $pos, $block, $name, $raw_args) {
			if ($name == 'use')
			{
				return sprintf('use %s;', $raw_args);
			}
		});

		$this->register_function('form_errors', [$this, 'formErrors']);

		$this->register_function('size_meter', [$this, 'sizeMeter']);
		$this->register_function('copy_button', [$this, 'copyButton']);
		$this->register_function('custom_colors', [$this, 'customColors']);
		$this->register_function('plugin_url', ['Paheko\Utils', 'plugin_url']);
		$this->register_function('diff', [$this, 'diff']);
		$this->register_function('display_permissions', [$this, 'displayPermissions']);

		$this->register_function('csrf_field', function ($params) {
			return Form::tokenHTML($params['key']);
		});

		$this->register_function('enable_upload_here', function ($params) {
			$csrf_key = 'upload_file_' . md5($params['path']);
			$url = Utils::getLocalURL('!common/files/upload.php?p=' . rawurlencode($params['path']));
			return sprintf(' data-upload-url="%s" data-upload-token-value="%s" data-upload-token-name="%s" ',
				htmlspecialchars($url),
				Form::tokenGenerate($csrf_key),
				Form::tokenFieldName($csrf_key),
			);
		});

		$this->register_block('linkmenu', [CommonFunctions::class, 'linkmenu']);

		$this->register_modifier('strlen', fn($a) => strlen($a ?? ''));
		$this->register_modifier('dump', ['KD2\ErrorManager', 'dump']);
		$this->register_modifier('get_country_name', ['Paheko\Utils', 'getCountryName']);
		$this->register_modifier('abs', function($a) { return abs($a ?? 0); });
		$this->register_modifier('percent_of', function($a, $b) { return !$b ? $b : round($a / $b * 100); });

		$this->register_modifier('linkify_transactions', function ($str) {
			$str = preg_replace_callback('/(?<=^|\s)(https?:\/\/.*?)(?=\s|$)/', function ($m) {
				return sprintf('<a href="%s" target="_blank">%1$s</a>', htmlspecialchars($m[1]));
			}, $str);

			return preg_replace_callback('/(?<=^|\s)#(\d+)(?=\s|$)/', function ($m) {
				return sprintf('<a href="%s%d">#%2$d</a>',
					Utils::getLocalURL('!acc/transactions/details.php?id='),
					$m[1]
				);
			}, $str);
		});

		$this->register_modifier('restore_snippet_markup', function ($str) {
			return preg_replace('!&lt;(/?mark)&gt;!', '<$1>', $str);
		});

		foreach (CommonModifiers::PHP_MODIFIERS_LIST as $name => $params) {
			$this->register_modifier($name, [CommonModifiers::class, $name]);
		}

		foreach (CommonModifiers::MODIFIERS_LIST as $key => $name) {
			$this->register_modifier(is_int($key) ? $name : $key, is_int($key) ? [CommonModifiers::class, $name] : $name);
		}

		foreach (CommonFunctions::FUNCTIONS_LIST as $key => $name) {
			$this->register_function(is_int($key) ? $name : $key, is_int($key) ? [CommonFunctions::class, $name] : $name);
		}

		$this->register_modifier('local_url', [Utils::class, 'getLocalURL']);

		// Overwrite default money modifiers
		$this->register_modifier('money', [CommonModifiers::class, 'money_html']);
		$this->register_modifier('money_currency', [CommonModifiers::class, 'money_currency_html']);
	}


	protected function formErrors($params)
	{
		$form = $this->getTemplateVars('form');

		if (!$form || !$form->hasErrors())
		{
			return '';
		}

		$errors = $form->getErrorMessages(!empty($params['membre']) ? true : false);

		foreach ($errors as &$error) {
			if ($error instanceof UserException) {
				if ($html = $error->getHTMLMessage()) {
					$message = $html;
				}
				else {
					$message = nl2br($this->escape($error->getMessage()));
				}

				if ($error->hasDetails()) {
					$message = '<h3>' . $message . '</h3>' . $error->getDetailsHTML();
				}

				$error = $message;
			}
			else {
				$error = nl2br($this->escape($error));
			}

			/* Not used currently
			$error = preg_replace_callback('/\[([^\]]*)\]\(([^\)]+)\)/',
				fn ($m) => sprintf('<a href="%s">%s</a>', Utils::getLocalURL($m[2]), $m[1] ?? $m[2])
			);
			*/
		}

		return '<div class="block error"><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
	}

	protected function customColors()
	{
		$config = defined('Paheko\INSTALL_PROCESS') ? null : Config::getInstance();

		$c1 = ADMIN_COLOR1;
		$c2 = ADMIN_COLOR2;
		$bg = ADMIN_BACKGROUND_IMAGE;

		if (!FORCE_CUSTOM_COLORS && $config) {
			$c1 = $config->get('color1') ?: $c1;
			$c2 = $config->get('color2') ?: $c2;

			if ($url = $config->fileURL('admin_background')) {
				$bg = $url;
			}
		}

		$out = '
		<style type="text/css">
		:root {
			--gMainColor: %s;
			--gSecondColor: %s;
			--gBgImage: url("%s");
		}
		</style>';

		if ($config && $url = $config->fileURL('admin_css')) {
			$out .= "\n" . sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $url);
		}

		return sprintf($out, CommonModifiers::css_hex_to_rgb($c1), CommonModifiers::css_hex_to_rgb($c2), $bg);
	}

	protected function diff(array $params)
	{
		if (isset($params['old'], $params['new'])) {
			$diff = \KD2\SimpleDiff::diff_to_array(false, $params['old'], $params['new'], $params['context'] ?? 3);
		}
		else {
			throw new \BadFunctionCallException('Paramètres old et new requis.');
		}

		$out = '<table class="diff">';

		if (isset($params['old_label'], $params['new_label'])) {
			$out .= sprintf(
				'<thead><tr><td colspan=2></td><th>%s</th><td></td><th>%s</th></tr></thead>',
				htmlspecialchars($params['old_label']),
				htmlspecialchars($params['new_label'])
			);
		}

		$out .= '<tbody>';

		$prev = key($diff);

		foreach ($diff as $i=>$line)
		{
			if ($i > $prev + 1)
			{
				$out .= '<tr class="separator"><td colspan="5"><hr /></td></tr>';
			}

			list($type, $old, $new) = $line;

			$class1 = $class2 = '';
			$t1 = $t2 = '';

			if ($type == \KD2\SimpleDiff::INS)
			{
				$class2 = 'ins';
				$t2 = '<span data-icon="➕"></span>';
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}
			elseif ($type == \KD2\SimpleDiff::DEL)
			{
				$class1 = 'del';
				$t1 = '<span data-icon="➖"></span>';
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}
			elseif ($type == \KD2\SimpleDiff::CHANGED)
			{
				$class1 = 'del';
				$class2 = 'ins';
				$t1 = '<span data-icon="➖"></span>';
				$t2 = '<span data-icon="➕"></span>';

				$lineDiff = \KD2\SimpleDiff::wdiff($old, $new);
				$lineDiff = htmlspecialchars($lineDiff, ENT_QUOTES, 'UTF-8');

				// Don't show new things in deleted line
				$old = preg_replace('!\{\+(?:.*)\+\}!U', '', $lineDiff);
				$old = str_replace('  ', ' ', $old);
				$old = str_replace('-] [-', ' ', $old);
				$old = preg_replace('!\[-(.*)-\]!U', '<del>\\1</del>', $old);

				// Don't show old things in added line
				$new = preg_replace('!\[-(?:.*)-\]!U', '', $lineDiff);
				$new = str_replace('  ', ' ', $new);
				$new = str_replace('+} {+', ' ', $new);
				$new = preg_replace('!\{\+(.*)\+\}!U', '<ins>\\1</ins>', $new);
			}
			else
			{
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}

			$out .= '<tr>';
			$out .= '<td class="line">'.($i+1).'</td>';
			$out .= '<td class="leftChange">'.$t1.'</td>';
			$out .= '<td class="leftText '.$class1.'">'.$old.'</td>';
			$out .= '<td class="rightChange">'.$t2.'</td>';
			$out .= '<td class="rightText '.$class2.'">'.$new.'</td>';
			$out .= '</tr>';

			$prev = $i;
		}

		$out .= '</tbody></table>';
		return $out;
	}

	protected function displayPermissions(array $params): string
	{
		$out = [];

		if (isset($params['section'], $params['level'])) {
			if (is_string($params['level'])) {
				$params['level'] = Session::ACCESS_LEVELS[$params['level']];
			}
			$list = [$params['section'] => Category::PERMISSIONS[$params['section']]];
			$perms = (object) ['perm_' . $params['section'] => $params['level']];
		}
		else {
			$perms = $params['permissions'];
			$list = Category::PERMISSIONS;
		}

		foreach ($list as $name => $config) {
			$access = $perms->{'perm_' . $name};
			$label = sprintf('%s : %s', $config['label'], $config['options'][$access]);
			$out[$name] = sprintf('<b class="access_%s %s" title="%s">%s</b>', $access, $name, htmlspecialchars($label), $config['shape']);
		}

		return implode(' ', $out);
	}

	protected function copyButton(array $params): string
	{
		return sprintf('<samp class="copy" onclick="this.nextSibling.click();" title="Cliquer pour copier dans le presse-papier">%s</samp><input type="button" onclick="var a = this.previousSibling; a.focus(); navigator.clipboard.writeText(a.innerText); this.value = \'Copié !\'; this.focus(); return false;" onblur="this.value = \'Copier\';" value="Copier" title="Copier dans le presse-papier" />', htmlspecialchars($params['label']));
	}

	// We cannot use <meter> here as Firefox sucks :(
	protected function sizeMeter(array $params): string
	{
		$out = sprintf('<%s class="quota %s">', $params['tag'] ?? 'span', $params['class'] ?? '');

		$attributes = '';

		if (!empty($params['href'])) {
			$params['meter_tag'] = 'a';
			$attributes .= sprintf(' href="%s"', htmlspecialchars(Utils::getLocalURL($params['href'])));
		}
		else {
			$params['meter_tag'] = 'span';
		}

		if (!empty($params['title'])) {
			$attributes .= sprintf(' title="%s"', htmlspecialchars($params['title']));
		}

		$more = '';

		if (isset($params['more'])) {
			$more = '<span class="more">' . $params['more'] . '</span>';
		}

		$text = sprintf($params['text'] ?? '%s', Utils::format_bytes($params['value']), Utils::format_bytes($params['total']));

		$out .= sprintf('<%s class="meter" style="--quota-percent: %s" %s><span class="text">%s</span>%s</%1$s>',
			$params['meter_tag'],
			round(100 * $params['value'] / ($params['total'] ?: 1)),
			$attributes,
			$text,
			$more
		);

		$out .= sprintf('</%s>', $params['tag'] ?? 'span');
		return $out;
	}
}
