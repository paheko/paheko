<?php

namespace Paheko\UserTemplate;

use Paheko\DB;
use Paheko\Utils;
use Paheko\UserException;

use Paheko\Users\DynamicFields;
use Paheko\Entities\Email\Email;

use KD2\SMTP;

use KD2\Brindille;
use KD2\Brindille_Exception;

class Modifiers
{
	const MODIFIERS_LIST = [
		'replace',
		'regexp_replace',
		'regexp_match',
		'match',
		'truncate',
		'excerpt',
		'atom_date',
		'xml_escape',
		'cdata_escape',
		'json_decode',
		'json_encode',
		'minify',
		'remove_leading_number',
		'get_leading_number',
		'spell_out_number',
		'parse_date',
		'parse_datetime',
		'parse_time',
		'math',
		'money_int' => [Utils::class, 'moneyToInteger'],
		'array_transpose' => [Utils::class, 'array_transpose'],
		'check_siret_number' => [Utils::class, 'checkSIRET'],
		'check_email',
		'gettype',
		'arrayval',
		'explode',
		'implode',
		'keys',
		'values',
		'has',
		'has_key',
		'in',
		'key_in',
		'sort',
		'ksort',
		'reverse',
		'max',
		'min',
		'array_to_list',
		'quote_sql_identifier',
		'quote_sql',
		'sql_where',
		'sql_user_fields',
		'url_encode',
		'url_decode',
		'urlencode' => [self::class, 'url_encode'],
		'count_words',
		'or',
		'uuid',
		'key',
	];

	const MODIFIERS_WITH_INSTANCE_LIST = [
		'call',
		'map',
	];

	const LEADING_NUMBER_REGEXP = '/^([\d.]+)\s*[.\)]\s*/';

	/**
	 * Call a user-defined function
	 * @example {{$variable|call:"my_test_function":$param1|escape}}
	 */
	static public function call(UserTemplate $tpl, int $line, $src, string $name, ...$params)
	{
		// Prepend first argument to list of arguments:
		// "string"|call:"test_function":42 => ["string", 42]
		array_unshift($params, $src);

		// Suppress any output
		ob_start();
		$r = $tpl->callUserFunction('modifier', $name, $params, $line);
		ob_end_clean();

		return $r;
	}

	static public function replace($str, $find, $replace = null): string
	{
		if (is_array($find) && null === $replace) {
			return strtr($str, $find);
		}

		return str_replace((string)$find, (string)$replace, (string)$str);
	}

	static public function regexp_replace($str, $pattern, $replace)
	{
		return preg_replace((string) $pattern, (string) $replace, (string) $str);
	}

	static public function regexp_match($str, $pattern)
	{
		return (int) preg_match((string) $pattern, (string) $str);
	}

	static public function match($str, $pattern)
	{
		return (int) (stripos($str, $pattern) !== false);
	}

	static public function check_email($str)
	{
		if (!trim((string)$str)) {
			return false;
		}

		try {
			Email::validateAddress((string)$str);
		}
		catch (UserException $e) {
			return false;
		}

		return true;
	}

	/**
	 * UTF-8 aware intelligent substr
	 * @param  string  $str         UTF-8 string
	 * @param  integer $length      Maximum string length
	 * @param  string  $placeholder Placeholder text to append at the string if it has been cut
	 * @param  boolean $strict_cut  If true then will cut in the middle of words
	 * @return string 				String cut to $length or shorter
	 * @example |truncate:10:" (click to read more)":true
	 */
	static public function truncate($str, $length = 80, $placeholder = '…', $strict_cut = false): string
	{
		if (mb_strlen($str) <= $length) {
			return $str;
		}

		$str = mb_substr($str, 0, $length);

		if (!$strict_cut) {
			$cut = preg_replace('/[^\s.,;!?]*$/su', '', $str);

			if (trim($cut) == '') {
				$cut = $str;
			}

			$str = $cut;
		}

		return trim($str) . $placeholder;
	}

	static public function excerpt($str, $length = 600): string
	{
		$str = strip_tags($str);
		$str = self::truncate($str, $length);
		$str = preg_replace("/\n{2,}/", '</p><p>', $str);
		return '<p>' . $str . '</p>';
	}

	static public function atom_date($date)
	{
		return Utils::date_fr($date, DATE_ATOM);
	}

	static public function xml_escape($str)
	{
		return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES);
	}

	static public function cdata_escape($str)
	{
		return str_replace(']]>', ']]]]><![CDATA[>', (string)$str);
	}

	static public function json_decode($str)
	{
		return json_decode($str, true);
	}

	static public function json_encode($obj)
	{
		return json_encode($obj, JSON_PRETTY_PRINT);
	}

	static public function minify(string $str, string $language = 'js'): string
	{
		// Remove comments
		$str = preg_replace('!/\*.*?\*/!s', '', $str);

		if ($language === 'css') {
			static $regexp = <<<'EOS'
				(?six)
				  # quotes
				  (
				    "(?:[^"\\]++|\\.)*+"
				  | '(?:[^'\\]++|\\.)*+'
				  )
				|
				  # ; before } (and the spaces after it while we're here)
				  \s*+ ; \s*+ ( } ) \s*+
				|
				  # all spaces around meta chars/operators
				  \s*+ ( [*$~^|]?+= | [{};,>~+-] | !important\b ) \s*+
				|
				  # spaces right of ( [ :
				  ( [[(:] ) \s++
				|
				  # spaces left of ) ]
				  \s++ ( [])] )
				|
				  # spaces left (and right) of :
				  \s++ ( : ) \s*+
				  # but not in selectors: not followed by a {
				  (?!
				    (?>
				      [^{}"']++
				    | "(?:[^"\\]++|\\.)*+"
				    | '(?:[^'\\]++|\\.)*+'
				    )*+
				    {
				  )
				|
				  # spaces at beginning/end of string
				  ^ \s++ | \s++ \z
				|
				  # double spaces to single
				  (\s)\s+
EOS;

			$str = preg_replace('%' . $regexp . '%', '$1$2$3$4$5$6$7$8', $str);
		}
		else {
			$str = preg_replace('!\s{2,}!', ' ', $str);
			$str = preg_replace('!(\{|\(|\[)\s+!', '$1', $str);
			$str = preg_replace('!\s+(\}|\)|\])!', '$1', $str);
		}

		return $str;
	}

	static public function remove_leading_number($str): string
	{
		return preg_replace(self::LEADING_NUMBER_REGEXP, '', trim($str));
	}

	static public function get_leading_number($str): ?string
	{
		preg_match(self::LEADING_NUMBER_REGEXP, $str, $match);
		return $match[1] ?? null;
	}

	static public function spell_out_number($number, string $locale = 'fr_FR', string $currency = 'euros'): string
	{
		$number = str_replace(',', '.', $number);
		$number = strtok($number, '.');
		$decimals = strtok('');

		$out = numfmt_create($locale, \NumberFormatter::SPELLOUT)->format((float) $number);
		$out .= ' ' . $currency;

		if ($decimals > 0) {
			$out .= sprintf(' et %s cents', numfmt_create($locale, \NumberFormatter::SPELLOUT)->format((float) $decimals));
		}

		return trim($out);
	}

	static public function parse_date($value)
	{
		if ($value instanceof \DateTimeInterface) {
			return $value->format('Y-m-d');
		}

		if (empty($value) || !is_string($value)) {
			return null;
		}

		if (preg_match('!^\d{2}/\d{2}/\d{2}$!', $value)) {
			return \DateTime::createFromFormat('!d/m/y', $value)->format('Y-m-d');
		}
		elseif (preg_match('!^\d{2}/\d{2}/\d{4}$!', $value)) {
			return \DateTime::createFromFormat('!d/m/Y', $value)->format('Y-m-d');
		}
		elseif (preg_match('!^\d{4}-\d{2}-\d{2}$!', $value)) {
			return $value;
		}
		else {
			return false;
		}
	}

	static public function parse_datetime($value)
	{
		if ($value instanceof \DateTimeInterface) {
			return $value->format('Y-m-d H:i:s');
		}

		if (empty($value) || !is_string($value)) {
			return null;
		}

		if (preg_match('!^\d{2}/\d{2}/\d{4}$\s+\d{2}:\d{2}!', $value)) {
			return \DateTime::createFromFormat('!d/m/Y', $value)->format('Y-m-d H:i');
		}
		elseif (preg_match('!^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}(:\d{2})?$!', $value, $match)) {
			return $value . (isset($match[1]) ? '' : ':00');
		}
		else {
			return false;
		}
	}

	static public function parse_time($value)
	{
		if ($value instanceof \DateTimeInterface) {
			return $value->format('H:i');
		}

		if (empty($value) || !is_string($value)) {
			return null;
		}

		if (false !== strpos($value, ':')) {
			$t = explode(':', $value);
		}
		elseif (false !== strpos($value, 'h')) {
			$t = explode('h', $value);
		}
		else {
			return null;
		}

		if (empty($t[0]) || !ctype_digit($t[0]) || $t[0] < 0 || $t[0] > 23) {
			return false;
		}

		if (empty($t[1]) || !ctype_digit($t[1]) || $t[1] < 0 || $t[1] > 59) {
			return false;
		}

		return sprintf('%02d:%02d', $t[0], $t[1]);
	}

	static public function math(string $expression, ... $params)
	{
		static $tokens_list = [
			'function'  => '(?:round|ceil|floor|cos|sin|tan|asin|acos|atan|sinh|cosh|tanh|abs|max|min|exp|sqrt|log10|log|pi|random_int)\(',
			'open'      => '\(',
			'close'     => '\)',
			'number'    => '-?\d+(?:[\.]\d+)?',
			'sign'      => '[+\-\*\/%]',
			'separator' => ',',
			'space'     => '\s+',
		];

		// Treat comma as dot in strings
		foreach ($params as &$param) {
			$param = str_replace(',', '.', (string)$param);
		}

		unset($param);

		$expression = vsprintf($expression, $params);

		try {
			$tokens = Brindille::tokenize($expression, $tokens_list);
		}
		catch (\InvalidArgumentException $e) {
			throw new Brindille_Exception('Invalid value: ' . $e->getMessage());
		}

		$stack = [];
		$expression = '';
		$value = null;
		$token = null;

		foreach ($tokens as $i => $token) {
			if ($token->type == 'function') {
				$stack[] = ['function' => $token->value, 'value' => &$value];
			}
			elseif ($token->type == 'open') {
				$stack[] = ['function' => null, 'value' => &$value];
			}
			elseif ($token->type == 'close') {
				$last = array_pop($stack);

				if (!$last) {
					throw new Brindille_Exception('Invalid closing parenthesis, on position ' . $token->offset);
				}
			}
			elseif ($token->type == 'separator') {
				if (empty(end($stack)['function'])) {
					throw new Brindille_Exception('Invalid comma outside of a function, on position ' . $token->offset);
				}
			}
			elseif ($token->type === 'number') {
				// Add spaces around numbers, so that 0--1 is treated as 0 - -1 = 0 + 1
				$token->value = ' ' . $token->value . ' ';
			}
			elseif ($token->type === 'sign') {
				if ($tokens[$i-1]->type === 'sign') {
					throw new Brindille_Exception('Invalid sign following a sign, on position ' . $token->offset);
				}
			}

			$expression .= $token->value;
		}

		if (count($stack)) {
			throw new Brindille_Exception('Unmatched open parenthesis, on position ' . $token->offset);
		}

		try {
			return @eval('return ' . $expression . ';') ?: 0;
		}
		catch (\Throwable $e) {
			throw new Brindille_Exception(sprintf('Syntax error: "%s" (in "%s")', $e->getMessage(), $expression), 0, $e);
		}
	}

	static public function map(UserTemplate $tpl, int $line, $array, string $modifier, ...$params): array
	{
		if (!is_array($array)) {
			throw new Brindille_Exception('Supplied argument is not an array');
		}

		if (!$tpl->checkModifierExists($modifier)) {
			throw new Brindille_Exception('Unknown modifier: ' . $modifier);
		}

		$out = [];

		foreach ($array as $key => $value) {
			$out[$key] = $tpl->callModifier($modifier, $line, $value, ...$params);
		}

		return $out;
	}

	static public function gettype($v): string
	{
		$type = gettype($v);

		switch($type) {
			case 'object':
				return 'array';
			case 'double':
				return 'float';
			case 'NULL':
				return 'null';
			case 'resource':
			case 'resource (closed)':
			case 'unknown type':
				throw new \LogicException('Unexpected type: ' . $type);
			default:
				return $type;
		}
	}

	static public function arrayval($v): array
	{
		return (array) $v;
	}

	static public function explode($string, string $separator): array
	{
		return explode($separator, (string)$string);
	}

	static public function implode($array, string $separator): ?string
	{
		if (!is_array($array) && !is_object($array)) {
			return $array;
		}

		return implode($separator, (array) $array);
	}

	static public function keys($array)
	{
		return array_keys((array)$array);
	}

	static public function key($array, $key)
	{
		return $array[$key] ?? null;
	}

	static public function values($array)
	{
		return array_values((array)$array);
	}

	static public function has($in, $value, $strict = false)
	{
		return in_array($value, (array)$in, $strict);
	}

	static public function in($value, $array, $strict = false)
	{
		return in_array($value, (array)$array, $strict);
	}

	static public function has_key($in, $key)
	{
		return array_key_exists($key, (array)$in);
	}

	static public function key_in($key, $array)
	{
		return array_key_exists($key, (array)$array);
	}

	static public function ksort($value)
	{
		$value = (array)$value;
		uksort($value, 'strnatcasecmp');
		return $value;
	}

	static public function sort($value)
	{
		$value = (array)$value;
		natcasesort($value);
		return $value;
	}

	static public function reverse($value)
	{
		return array_reverse((array)$value, true);
	}

	static public function max($value)
	{
		return max((array)$value);
	}

	static public function min($value)
	{
		return min((array)$value);
	}

	static public function array_to_list($value, int $i = 0): string
	{
		$out = '';

		foreach ((array)$value as $k => $v) {
			$out .= str_repeat(' ', $i);

			if (!is_int($k)) {
				$out .= $k . ' = ';
			}
			else {
				$out .= ($k + 1) . ' = ';
			}

			if (is_array($v) || is_object($v)) {
				$out .= "\n";
				$out .= self::array_to_list($v, $i + 1);
			}
			else {
				$out .= $v;
			}

			$out .= "\n";
		}

		return rtrim($out);
	}

	static public function quote_sql_identifier($in, string $prefix = '')
	{
		if (null === $in) {
			return '';
		}

		$db = DB::getInstance();

		if ($prefix) {
			$prefix = $db->quoteIdentifier($prefix) . '.';
		}

		if (is_array($in) || is_object($in)) {
			return array_map(fn($a) => $prefix . $db->quoteIdentifier($a), (array) $in);
		}

		return $prefix . $db->quoteIdentifier($in);
	}

	static public function quote_sql($in)
	{
		if (null === $in) {
			return '';
		}

		$db = DB::getInstance();

		if (is_array($in) || is_object($in)) {
			return array_map([$db, 'quote'], (array) $in);
		}

		return $db->quote($in);
	}

	static public function sql_where(...$args)
	{
		return DB::getInstance()->where(...$args);
	}

	static public function sql_user_fields($list, string $prefix = '', string $glue = ' '): string
	{
		$db = DB::getInstance();
		$prefix = $prefix ? $db->quoteIdentifier($prefix) . '.' : '';
		$out = [];
		$glue = $db->quote($glue);
		$list = (array) $list;

		foreach ($list as $field) {
			if (!DynamicFields::get($field)) {
				continue;
			}

			if (count($list) === 1) {
				return $prefix . $db->quoteIdentifier($field);
			}

			$out[] = sprintf('COALESCE(%s || %s%s, \'\')', $glue, $prefix, $db->quoteIdentifier($field));
		}

		if (!count($out)) {
			return 'NULL';
		}

		return sprintf('LTRIM(%s, %s)', implode(' || ', $out), $glue);
	}

	static public function url_encode($str): string
	{
		return rawurlencode($str ?? '');
	}

	static public function url_decode($str): string
	{
		return rawurldecode($str ?? '');
	}

	static public function count_words($str): int
	{
		return preg_match_all('/\S+/u', $str);
	}

	static public function or($in, $else)
	{
		if (empty($in) || (is_string($in) && trim($in) === '')) {
			return $else;
		}

		return $in;
	}

	static public function uuid()
	{
		return Utils::uuid();
	}
}
