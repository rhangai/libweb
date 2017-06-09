<?php
namespace LibWeb;

class Validator {
	
	public static function validate( $value, $rule ) {
		$state = new validator\State( $value );
		validator\Rule::validateState( $rule, $state );
		return $state->value;
	}
	
};

require_once dirname(__DIR__)."/vendor/autoload.php";

$data = array( "a" => "oi" );

Validator::validate( $data, array(
	"a" => 100
) );

/*
  $rules = array(
     "nome"    => v::noop(),
     "arquivo" => v::object(),
  );

 */

// /**
//  * Helper class with the actual rules
//  */
// class ValidatorRules {
	
// 	public static $rules = array(
// 		'initial'      => array( '\LibWeb\ValidatorRules', 'initial' ),
// 		'noop'         => array( '\LibWeb\ValidatorRules', 'noop' ),
// 		'store'        => array( '\LibWeb\ValidatorRules', 'store' ),
// 		'restore'      => array( '\LibWeb\ValidatorRules', 'restore' ),
// 		'call'         => array( '\LibWeb\ValidatorRules', 'call' ),
// 		'trim'         => array( '\LibWeb\ValidatorRules', 'trim' ),
// 		'ltrim'        => array( '\LibWeb\ValidatorRules', 'ltrim' ),
// 		'rtrim'        => array( '\LibWeb\ValidatorRules', 'rtrim' ),
// 		'strval'       => array( '\LibWeb\ValidatorRules', 'strval' ),
// 		'intval'       => array( '\LibWeb\ValidatorRules', 'intval' ),
// 		'floatval'     => array( '\LibWeb\ValidatorRules', 'floatval' ),
// 		'str_replace'  => array( '\LibWeb\ValidatorRules', 'str_replace' ),
// 		'preg_replace' => array( '\LibWeb\ValidatorRules', 'preg_replace' ),
// 		'substr'       => array( '\LibWeb\ValidatorRules', 'substr' ),
// 		'is_int'       => array( '\LibWeb\ValidatorRules', 'is_int' ),
// 		'is_numeric'   => array( '\LibWeb\ValidatorRules', 'is_numeric' ),
// 		'length'       => array( '\LibWeb\ValidatorRules', 'length' ),
// 		'date'         => array( '\LibWeb\ValidatorRules', 'date' ),
// 		'email'        => array( '\LibWeb\ValidatorRules', 'email' ),
// 	);

// 	/// Get the initial value
// 	public static function initial( $state, $args ) {
// 		$state->value = $state->initial;
// 	}
// 	/// Noop value
// 	public static function noop( $state, $args ) {
// 	}
// 	/// Store a value
// 	public static function store( $state, $args ) {
// 		$name = $args[0];
// 		$state->store[ $name ] = $state->value;
// 	}
// 	/// Restore a value
// 	public static function restore( $state, $args ) {
// 		$name = @$args[0];
// 		$state->value = (!$name) ? $state->initial : $state->store[ $name ];
// 	}
// 	/// Call the user function
// 	public static function call( $state, $args ) {
// 		$fn  = $args[ 0 ];
// 		$ret = call_user_func_array( $fn, array_merge( array( $state->value ), array_slice( $args, 1 ) ) );
// 		if ( $ret === false ) {
// 			$state->errors[] = array( "name" => "call", "data" => $fn );
// 			return;
// 		}
// 		if ( $ret === true )
// 			return;
// 		$state->value = $ret;
// 	}
// 	/// Trim the string
// 	public static function trim( $state, $args ) {
// 		return self::call( $state, array( 'trim' ) );
// 	}
// 	/// Trim the string - Left
// 	public static function ltrim( $state, $args ) {
// 		return self::call( $state, array( 'ltrim' ) );
// 	}
// 	/// Trim the string - Right
// 	public static function rtrim( $state, $args ) {
// 		return self::call( $state, array( 'rtrim' ) );
// 	}
// 	/// Get the value as string
// 	public static function strval( $state, $args ) {
// 		return self::call( $state, array( 'strval' ) );
// 	}
// 	/// Get the value as int
// 	public static function intval( $state, $args ) {
// 		return self::call( $state, array( 'intval' ) );
// 	}
// 	/// Get the value as float
// 	public static function floatval( $state, $args ) {
// 		return self::call( $state, array( 'floatval' ) );
// 	}
// 	/// String replace a value
// 	public static function str_replace( $state, $args ) {
// 		$state->value = str_replace( @$args[0], @$args[1], $state->value );
// 	}
// 	/// Regex replace a value
// 	public static function preg_replace( $state, $args ) {
// 		$state->value = preg_replace( @$args[0], @$args[1], $state->value );
// 	}
// 	/// Replace a substr
// 	public static function substr( $state, $args ) {
// 		if ( count($args) === 1 )
// 			$state->value = substr( $state->value, $args[0] );
// 		else if ( count($args) === 2 )
// 			$state->value = substr( $state->value, $args[0], $args[1] );
// 		else
// 			throw new \InvalidArgumentException( "Invalid arguments for substr validation" );
// 	}
// 	/// Check if a given value is an int or a string representation of a digit
// 	public static function is_int( $state, $args ) {
// 		$error = false;
// 		if ( is_int( $state->value ) ) {
// 			$state->value = strval( $state->value );
// 		} else if ( is_string( $state->value ) ) {
// 			if ( !ctype_digit( $state->value ) )
// 				$error = true;
// 		} else {
// 			$error = true;
// 		}
// 		if ( $error )
// 			$state->errors[] = array( "name" => "is_int" );
// 	}
// 	/// Check if a given value is a numeric decimal number
// 	public static function is_numeric( $state, $args ) {
// 		$error        = false;
// 		$separator    = @$args[0];

// 		if ( !$separator ) {
// 			$separator = $outSeparator = ".";
// 		} else if ( strlen($separator) === 1 ) {
// 			$outSeparator = $separator;
// 		} else if ( strlen($separator) >= 2 ) {
// 			$outSeparator = $separator[ 1 ];
// 			$separator    = $separator[ 0 ];
// 		}
// 		if ( is_int( $state->value ) ) {
// 			$state->value = strval( $state->value );
// 		} else if ( is_string( $state->value ) ) {
// 			$parts = explode( $separator, $state->value );
// 			if ( count($parts) === 1 )
// 				$error = !ctype_digit( $parts[0] );
// 			else if ( count($parts) === 2 ) {
// 				$error = !ctype_digit( $parts[0] ) || !ctype_digit( $parts[1] );
// 				if ( !$error )
// 					$state->value = $parts[0] . $outSeparator . $parts[1];
// 			} else {
// 				$error = true;
// 			}
// 		} else if ( is_float( $state->value ) ) {
// 			$state->value = strval( $state->value );
// 			if ( $outSeparator !== "." )
// 				$state->value = str_replace( ".", $outSeparator, $state->value );
// 		} else {
// 			$error = true;
// 		}
// 		if ( $error )
// 			$state->errors[] = array( "name" => "is_numeric" );
// 	}
// 	/// Length
// 	public static function length( $state, $args ) {
// 		$options = $args[0];
// 		if ( is_int( $options ) ) {
// 			if ( is_int( @$args[1] ) )
// 				$options = array( "min" => $options, "max" => $args[1] );
// 			else
// 				$options = array( "min" => $options );
// 		}
// 		if ( !is_array($options) )
// 			throw new \Exception( "Invalid options for length" );

// 		$len = strlen( $state->value );
// 		$min = @$options["min"];
// 		$max = @$options["max"];
// 		if ( ($min != null) && ( $len < $min ) ) {
// 			$state->errors[] = array( "name" => "length", "data" => array( "options" => $options, "value" => $state->value ) );
// 			return;
// 		} else if ( ($max != null) && ( $len > $max ) ) {
// 			$state->errors[] = array( "name" => "length", "data" => array( "options" => $options, "value" => $state->value ) );
// 			return;
// 		}
// 	}
// 	/// Date
// 	public static function date( $state, $args ) {
// 		$format = $args[0];
// 	    $date   = \DateTime::createFromFormat( $format, $state->value );
// 		if ( $date === false ) {
// 			$state->errors[] = array( 'name' => 'date', 'data' => \DateTime::getLastErrors() );
// 			return;
// 		}
// 		$date->setTime( 0, 0, 0 );
// 		$state->value = $date->format('Y-m-d H:i:s');
// 	}
// 	/// Email
// 	public static function email( $state, $args ) {
// 		$email = filter_var( $state->value, FILTER_SANITIZE_EMAIL );
// 		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
// 			$state->errors[] = array( "name" => "email", "data" => $email );
// 			return;
// 		};
// 		$state->value = $email;
// 	}
// };

// /// A chain of rules 
// class ValidatorChain {

// 	private $rules;
// 	public  $flags;
	
// 	public function __construct( $rules = array(), $flags = 0 ) {
// 		$this->rules = $rules;
// 		$this->flags = $flags;
// 	}
// 	/// Add a new rule to this chain
// 	public function __call( $name, $args ) {
// 		if ( @!ValidatorRules::$rules[ $name ] )
// 			throw new \Exception( "Invalid rule ".$name );
		
// 		$this->rules[] = (object) array(
// 			"name" => $name,
// 			"args" => $args
// 		);
// 		return $this;
// 	}
// 	/// Validate the check
// 	public function validateCheck( $value ) {
// 		$state = self::createState( $value );
// 		return $this->validateState( $state );
// 	}
// 	/// Validate the state agains this chain
// 	public function validateState( $state ) {
// 		foreach ( $this->rules as $rule ) {
// 			if ( $state->errors )
// 				break;
// 			$this->_applyRule( $state, $rule );
// 		}
// 		return $state;
// 	}
// 	// Create the state to apply the validations
// 	public static function createState( $value ) {
// 		return (object) array(
// 			"initial"  => $value,
// 			"value"    => $value,
// 			"store"    => array(),
// 			"errors"   => array(),
// 		);
// 	}
// 	// Apply the rule
// 	private function _applyRule( $state, $rule ) {
// 		if ( $rule instanceof \Closure ) {
// 		    call_user_func( $rule, $state );
// 		} else {
// 			$cb = ValidatorRules::$rules[ $rule->name ];
// 			call_user_func( $cb, $state, $rule->args );
// 		}
// 	}
// 	/// Optional
// 	public function optional() {
// 		return new ValidatorChain( $this->rules, $this->flags | Validator::FLAG_OPTIONAL );
// 	}
// 	/// Non optional
// 	public function exists() {
// 		return new ValidatorChain( $this->rules, $this->flags & (~Validator::FLAG_OPTIONAL) );
// 	}
// 	/// Array
// 	public function arrayOf( $rule ) {
// 		$chain = new ValidatorChain( $this->rules, $this->flags | Validator::FLAG_SKIP_DEFAULT );
// 		$chain->rules[] = function( $state ) use ($rule) {
// 			$ary = $state->value;
// 			if ( !is_array( $ary ) ) {
// 				$state->errors[] = array( "name" => "arrayOf", "data" => "Not an array" );
// 				return;
// 			}

// 			$ret    = array();
// 			$errors = array();
// 			foreach ( $ary as $key => $value ) {
// 				$itemState = ValidatorChain::createState( $value );
// 				$rule->validateState( $itemState );
// 				if ( $itemState->errors ) {
// 					$errors[ $key ] = $itemState->errors;
// 				} else {
// 					$ret[ $key ] = $itemState->value;
// 				}
// 			}
// 			if ( $errors )
// 				$state->errors[] = array( "name" => "arrayOf", "data" => $errors );
// 			else
// 				$state->value    = $ret;
// 		};
// 		return $chain;
// 	}
// 	/// Object
// 	public function obj( $rule ) {
// 		$chain = new ValidatorChain( $this->rules, $this->flags | Validator::FLAG_SKIP_DEFAULT );
// 		$chain->rules[] = function( $state ) use ($rule) {
// 			$obj = $state->value;
// 			if ( is_array( $obj ) ) {
// 				$obj = (object) $obj;
// 			} else if ( is_object( $obj ) ) {}
// 			else {
// 				$state->errors[] = array( "name" => "obj", "data" => "Not an object/array" );
// 				return;
// 			}

// 			$err       = null;
// 			$validated = Validator::validateObjSafe( $err, $obj, $rule );
// 			if ( $err )
// 				$state->errors[] = array( "name" => "arrayOf", "data" => $err );
// 			else
// 				$state->value    = $validated;
// 		};
// 		return $chain;
// 	}
// };

// /**
//  * Validator class
//  */
// class Validator {
// 	const FLAG_OPTIONAL     = 0x01;
// 	const FLAG_SKIP_DEFAULT = 0x02;
// 	private static $defaultValidationRulesCache = null;
// 	/**
// 	 * Register a new rule
// 	 */
// 	public static function registerRule( $name, $rule ) {
// 		if ( is_callable( $rule, false, $ruleName ) )
// 			ValidatorRules::$rules[ $name ] = $ruleName;
// 	}
// 	/**
// 	 * Get the default validation rules
// 	 */
// 	public static function defaultValidationRules() {
// 		if ( !self::$defaultValidationRulesCache ) {
// 			self::$defaultValidationRulesCache = Validator::trim();
// 		}
// 		return self::$defaultValidationRulesCache;
// 	}
// 	/**
// 	 * Validate agains a single rule
// 	 */
// 	public static function validate( $value, $rule, $defaultRules = true ) {
// 		if ( $defaultRules === true )
// 			$defaultRules = self::defaultValidationRules();

// 		$state = ValidatorChain::createState( $value );
// 		if ( $defaultRules && $state->value )
// 			$state = $defaultRules->validateState( $state );
// 		$state = $rule->validateState( $state );
// 		if ( $state->errors )
// 			throw new ValidatorException( $state );
// 		return $state->value;
// 	}
// 	/**
// 	 * Validate a full object using a safe (no throw) parameter
// 	 */
// 	public static function validateObjSafe( &$err, $obj, $rules, $defaultRules = true ) {
// 		if ( $defaultRules === true )
// 			$defaultRules = self::defaultValidationRules();
// 		$assoc     = (array) $obj;
// 		$validated = array();
// 		$errors    = array();
// 		foreach ( $rules as $key => $rule ) {
// 			if ( $key && ($key[strlen($key)-1] === "?" ) ) {
// 				$key  = substr( $key, 0, -1 );
// 				$rule = $rule->optional();
// 			}

// 			$skipDefault = false;
// 			if ( $key && ($key[0] === "!" ) ) {
// 				$key  = substr( $key, 1 );
// 				$skipDefault = true;
// 			}
// 			if ( $rule->flags & self::FLAG_SKIP_DEFAULT )
// 				$skipDefault = true;

// 			$state = ValidatorChain::createState( @$assoc[ $key ] );
// 			if ( !$skipDefault && $defaultRules && $state->value )
// 				$state = $defaultRules->validateState( $state );

// 			if ( !$state->value ) {
// 				if ( $rule->flags & self::FLAG_OPTIONAL )
// 					continue;
// 				$state->errors[] = array( "name" => "exists" );
// 			} else {
// 				$state = $rule->validateState( $state );
// 			}

// 			if ( $state->errors )
// 				$errors[ $key ] = $state->errors;
// 			else
// 				$validated[ $key ] = $state->value;
// 		}
// 		if ( $errors ) {
// 			$err = (object) array( "value" => (object) $obj, "errors" => $errors );
// 			return false;
// 		}
// 		return (object) $validated;
// 	}

// 	/**
// 	 * Validate a full object
// 	 */
// 	public static function validateObj( $obj, $rules, $defaultRules = true ) {
// 		$err       = null;
// 		$validated = self::validateObjSafe( $err, $obj, $rules, $defaultRules );
// 		if ( $err )
// 			throw new ValidatorException( $err, array_keys( $err->errors ) );
// 		return $validated;
// 	}

	
// 	/// Create a chain with the given validator
// 	public static function __callStatic( $name, $args ) {
// 		$chain = new ValidatorChain;
// 		if ( is_callable( array( $chain, $name ) ) )
// 			return call_user_func_array( array( $chain, $name ), $args );
// 		return $chain->__call( $name, $args );
// 	}
	
// };