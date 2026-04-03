<?php

namespace Paheko\UserTemplate;

use Paheko\TemplateException;
use Paheko\Utils;
use Paheko\UserException;

/**
 * User-defined functions (experimental)
 * @deprecated FIXME experimental
 */
class UserTemplateDefinedFunctions
{
	/**
	 * List of user-defined functions
	 * @var array
	 */
	protected $user_modifiers = [];
	protected $user_functions = [];
	protected $user_sections = [];

	public function allowUserDefinedFunctions(UserTemplate $tpl)
	{
		// Modifiers
		//'call' => ['pass_object' => true, 'types' => [null, 'string', '...' => null]],
		//'map' => ['pass_object' => true, 'types' => ['array', 'string', '...' => null]],

		// Compile sections
		//'#define'     => [self::class, 'defineStart'],
		//'else:define' => [self::class, 'defineElse'],
		//'/define'     => [self::class, 'defineEnd'],


		// Functions
		//'call',

		// Compile functions
		//':return'   => [self::class, 'compile_return'],
		//':exit'     => [self::class, 'compile_exit'],
		//':yield'    => [self::class, 'compile_yield'],
	}

	/**
	 * Call a user-defined function (using {{#define}} and {{:call}} {{#call}} etc.)
	 */
	public function callUserFunction(string $context, string $name, array $params, int $line)
	{
		if ($context !== 'modifier' && $context !== 'function' && $context !== 'section') {
			throw new \LogicException('Invalid user function context: ' . $context);
		}

		if (!array_key_exists($name, $this->{'user_' . $context . 's'})) {
			throw new TemplateException(sprintf('call to undefined user %s \'%s\'', $context, $name));
		}

		return $this->{'user_' . $context . 's'}[$name]($params, $line);
	}

	/**
	 * Register a new user-defined function (this can either be a modifier, function or section)
	 */
	public function registerUserFunction(string $context, string $name, callable $function): void
	{
		if ($context !== 'modifier' && $context !== 'function' && $context !== 'section') {
			throw new \LogicException('Invalid user function context: ' . $context);
		}

		if (!preg_match(self::RE_VALID_VARIABLE_NAME, $name)) {
			throw new TemplateException(sprintf('Invalid syntax for function name \'%s\'', $name));
		}

		$this->{'user_' . $context . 's'}[$name] = $function;
	}

	/**
	 * Copy user-defined functions between UserTemplate instances
	 * This is so that a user-defined function defined in an included template
	 * can be called by the parent template.
	 *
	 * This should be in Functions::include
	 */
	public function copyUserFunctionsTo(UserTemplate $target): void
	{
		$contexts = ['modifier', 'function', 'section'];

		foreach ($contexts as $context) {
			foreach ($this->{'user_' . $context . 's'} as $name => $function) {
				$target->registerUserFunction($context, $name, $function->bindTo($target));
			}
		}
	}

	#[TemplateFunction('call')]
	#[TemplateParam('function')]
	static public function function_call(array $params, UserTemplate $tpl, int $line): void
	{
		if (empty($params['function'])) {
			throw new TemplateException('Missing "function" parameter for "call" function');
		}

		$name = $params['function'];
		unset($params['function']);
		$tpl->callUserFunction('function', $name, $params, $line);
	}

	#[TemplateSection('call')]
	#[TemplateParam('section')]
	static public function section_call(array $params, UserTemplate $tpl, int $line): ?\Generator
	{
		if (empty($params['section'])) {
			throw new TemplateException('Missing "section" parameter for "call" section');
		}

		$name = $params['section'];
		unset($params['section']);

		$r = $tpl->callUserFunction('section', $name, $params, $line);

		if (!is_iterable($r)) {
			return null;
		}

		foreach ($r as $key => $value) {
			if (is_array($value)) {
				yield $value;
			}
			else {
				yield compact('key', 'value');
			}
		}
	}

	#[TemplateFunction('return')]
	#[TemplateParam('value')]
	static public function compile_return(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		// Allow {{:return value="test"}} inside a user-defined modifier only
		if ($parent && ($parent[2]['context'] ?? null) === 'modifier') {
			$params = $tpl->_parseArguments($params_str, $line);

			if (!isset($params['value'])) {
				$params = 'null';
			}
			else {
				$params = $params['value'];
			}

			return sprintf('<?php return %s; ?>', $params);
		}
		// But not outside
		elseif (!empty($params_str)) {
			throw new TemplateException('"return" function cannot have parameters in this context');
		}

		return '<?php return; ?>';
	}

	#[TemplateFunction('exit')]
	static public function compile_exit(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		if (!$parent) {
			throw new TemplateException('"exit" function cannot be called in this context');
		}

		return '<?php return; ?>';
	}

	#[TemplateFunction('yield')]
	static public function compile_yield(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$parent = $tpl->_getStack($tpl::SECTION, 'define');

		// Only allow {{:yield}} inside a user-defined function
		if (!$parent || ($parent[2]['context'] ?? null) !== 'section') {
			throw new TemplateException('"yield" can only be used inside a "define" section');
		}

		$params = $tpl->_parseArguments($params_str, $line);

		return sprintf('<?php yield %s; ?>', $tpl->_exportArguments($params));
	}


	#[TemplateSection('define')]
	#[TemplateParam('modifier')]
	#[TemplateParam('function')]
	#[TemplateParam('section')]
	/**
	 * Start of user-defined function block
	 */
	static public function defineStart(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$params = $tpl->_parseArguments($params_str, $line);
		$context = array_intersect_key(['modifier' => null, 'function' => null, 'section' => null], $params);

		if (count($context) > 1) {
			throw new TemplateException('"define" only allows one of "modifier", "function" or "section" parameters');
		}
		elseif (!count($context)) {
			throw new TemplateException('"define": missing "modifier", "function" or "section" parameter');
		}

		$context = key($context);
		$name = $tpl->getValueFromArgument($params[$context]);

		if (!preg_match($tpl::RE_VALID_VARIABLE_NAME, $name)) {
			throw new TemplateException(sprintf('Invalid syntax for %s name \'%s\'', $context, $name));
		}

		// Avoid weird stuff (like defining a function inside a function):
		// only allow functions to be defined at the root level
		if (count($tpl->_stack)) {
			throw new TemplateException(sprintf('%s cannot be defined inside a condition or section', $context));
		}

		$tpl->_push($tpl::SECTION, 'define', compact('context', 'name'));

		return sprintf('<?php '
			. '$this->registerUserFunction(%s, %s, function (array $params, int $line) { '
			// Store function name here, might be useful for handling errors
			. '$context = %1$s; $name = %2$s; '
			// Pass variables to template, either as '$params' variable for modifiers,
			// or extract all parameters as variables for functions/sections
			. '$this->_variables[] = %s; '
			// Put all function body in a try
			. 'try { ?>',
			var_export($context, true),
			var_export($name, true),
			$context === 'modifier' ? 'compact(\'params\')' : '$params'
		);
	}

	static public function defineElse(string $name, string $params_str, UserTemplate $tpl, int $line): void
	{
		throw new TemplateException('\'else\' cannot be used with #define sections');
	}

	static public function defineEnd(string $name, string $params_str, UserTemplate $tpl, int $line): string
	{
		$last = $tpl->_lastName();

		if ($last !== 'define') {
			throw new TemplateException(sprintf('"%s": block closing does not match last block "%s" opened', $name . $params_str, $last));
		}

		$tpl->_pop();

		return '<?php } '
			// Prepend function name to error
			. 'catch (\Paheko\TemplateException|\KD2\Brindille_Exception $e) { throw new \Paheko\TemplateException(sprintf("Error in \'%s\' %s: %s", $name, $context, $e->getMessage())); } '
			// Always remove current context variables even if return was used (should not be necessary anymore) FIXME
			//. 'finally { array_pop($this->_variables); } '
			. '}); ?>';
	}

	/**
	 * Call a user-defined function
	 * @example {{$variable|call:"my_test_function":$param1|escape}}
	 */
	static public function modifier_call(UserTemplate $tpl, int $line, $src, string $name, ...$params)
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

	static public function modifier_map(UserTemplate $tpl, int $line, $array, string $modifier, ...$params): array
	{
		if (!is_array($array)) {
			throw new TemplateException('Supplied argument is not an array');
		}

		if (!$tpl->checkModifierExists($modifier)) {
			throw new TemplateException('Unknown modifier: ' . $modifier);
		}

		$out = [];

		foreach ($array as $key => $value) {
			$out[$key] = $tpl->callModifier($modifier, $line, $value, ...$params);
		}

		return $out;
	}
}
