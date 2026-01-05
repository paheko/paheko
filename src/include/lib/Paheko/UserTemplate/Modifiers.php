<?php

namespace Paheko\UserTemplate;

use Paheko\DB;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;

use Paheko\Users\DynamicFields;
use Paheko\Entities\Email\Email;

use KD2\Smartyer;
use KD2\SMTP;

use KD2\Brindille;
use KD2\Brindille_Exception;

class Modifiers
{
	const MODIFIERS_LIST = [
		'replace' => ['scalar+=', 'array=|scalar+=', 'scalar+'],
		'regexp_replace' => ['scalar+=', 'string+=', 'scalar+='],
		'regexp_match' => ['scalar+=', 'string+='],
		'match' => ['scalar+=', 'scalar+='],
		'truncate' => ['scalar+=', 'numeric', 'string+', 'bool+'],
		'excerpt' => ['string+', 'numeric'],
		'atom_date' => ['?DateTimeInterface|string|int'],
		'xml_escape' => ['string+'],
		'cdata_escape' => ['string+'],
		'entities_escape' => ['string+'],
		'json_decode' => ['string+'],
		'json_encode' => [null],
		'minify' => ['string+', 'string+'],
		'remove_leading_number' => ['string+'],
		'get_leading_number' => ['string+'],
		'spell_out_number' => ['numeric', '?string', '?string'],
		'parse_date' => ['?DateTimeInterface|scalar'],
		'parse_datetime' => ['?DateTimeInterface|scalar', '?string'],
		'parse_time' => ['?DateTimeInterface|scalar'],
		'math' => ['scalar+', '...' => 'scalar+'],
		'money_int' => ['callback' => [Utils::class, 'moneyToInteger'], 'types' => ['scalar+=']],
		'array_transpose' => ['callback' => [Utils::class, 'array_transpose'], 'types' => ['array=']],
		'check_siret_number' => ['callback' => [Utils::class, 'checkSIRET'], 'types' => ['scalar+=']],
		'check_email' => ['scalar+='],
		'gettype' => [null],
		'arrayval' => [null],
		'explode' => ['scalar+', 'scalar+'],
		'implode' => ['?array|string', 'scalar+'],
		'flip' => ['?array'],
		'key' => ['?array', 'scalar+'],
		'keys' => ['?array'],
		'value' => ['?array', 'scalar+'],
		'values' => ['?array'],
		'has' => ['?array', 'scalar+'],
		'has_key' => ['?array', 'scalar+'],
		'in' => ['scalar+', 'array'],
		'key_in' => ['scalar+', 'array'],
		'sort' => ['?array'],
		'ksort' => ['?array'],
		'reverse' => ['?array'],
		'filter' => ['?array'],
		'max',
		'min',
		'array_to_list' => ['?array'],
		'quote_sql_identifier' => ['scalar+', 'scalar+'],
		'quote_sql' => ['scalar+'],
		'sql_where',
		'sql_user_fields',
		'url_encode' => ['scalar+'],
		'url_decode' => ['scalar+'],
		'urlencode' => ['callback' => [self::class, 'url_encode'], 'types' => ['scalar+']],
		'count_words' => ['scalar+'],
		'uuid' => [],
		'call' => ['pass_object' => true, 'types' => [null, 'string', '...' => null]],
		'map' => ['pass_object' => true, 'types' => ['array', 'string', '...' => null]],
	];

	const LEADING_NUMBER_REGEXP = '/^(\d{1,3})(?:\s+|\s*[.\)]\s*)/';

	/**
	 * Call a user-defined function
	 * EXPERIMENTAL! DO NOT USE YET! FIXME
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

	static public function replace(string $str, $find, string $replace = ''): string
	{
		if (is_array($find) && '' === $replace) {
			return strtr($str, $find);
		}

		return str_replace((string)$find, $replace, $str);
	}

	static public function regexp_replace(string $str, string $pattern, string $replace)
	{
		return preg_replace($pattern, $replace, $str);
	}

	static public function regexp_match(string $str, string $pattern)
	{
		return (int) preg_match($pattern, $str);
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

	static public function truncate(string $str, $length = 80, string $placeholder = '…', bool $strict_cut = false): string
	{
		return Smartyer::truncate($str, (int) $length, $placeholder, $strict_cut);
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

	/**
	 * @deprecated
	 */
	static public function xml_escape($str)
	{
		return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES);
	}

	/**
	 * @deprecated
	 */
	static public function cdata_escape($str)
	{
		return str_replace(']]>', ']]]]><![CDATA[>', (string)$str);
	}

	/**
	 * @deprecated
	 */
	static public function entities_escape($str)
	{
		return htmlentities((string)$str);
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
		$number = str_replace(',', '.', (string)$number);
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

	static public function parse_datetime($value, ?string $format = '')
	{
		if ($format === 'RFC3339') {
			$format = DATE_RFC3339;
		}
		elseif ($format === 'LOCAL') {
			$format = 'Y-m-d\TH:i';
		}
		else {
			$format = 'Y-m-d H:i';
		}

		if ($value instanceof \DateTimeInterface) {
			return $value->format($format);
		}

		if (empty($value) || !is_string($value)) {
			return null;
		}

		$value = Utils::parseDateTime($value);

		if ($value === null) {
			return false;
		}

		return $value->format($format);
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

	static public function math($expression, ... $params)
	{
		static $tokens_list = [
			'function'  => '(?:round|ceil|floor|cos|sin|tan|asin|acos|atan2?|deg2rad|sinh|cosh|tanh|abs|max|min|exp|sqrt|log10|log|pi|random_int)\(',
			'open'      => '\(',
			'close'     => '\)',
			'number'    => '-?\d+(?:[\.]\d+)?',
			'sign'      => '[+\-\*\/%]',
			'separator' => ',',
			'space'     => '\s+',
		];

		if (is_array($expression) || is_object($expression) || trim((string)$expression) === '') {
			throw new Brindille_Exception('Invalid empty or array value passed to math modifier');
		}

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

	/**
	 * EXPERIMENTAL! DO NOT USE!
	 */
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

	static public function filter($v): array
	{
		return array_filter((array) $v);
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

	static public function flip($array)
	{
		return array_flip((array)$array);
	}

	static public function key($array, $value)
	{
		$key = array_search($value, $array, true);

		if ($key === false) {
			return null;
		}

		return $key;
	}

	static public function value($array, $key)
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

	static public function max(...$values)
	{
		return max(...$values);
	}

	static public function min(...$values)
	{
		return min(...$values);
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

	static public function uuid()
	{
		return Utils::uuid();
	}
}
