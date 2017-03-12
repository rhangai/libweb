<?php namespace LibWeb;

/**
 * Helper class with the actual rules
 */
class ValidatorRules {
	
	public static $rules = array(
		'initial'  => array( '\LibWeb\ValidatorRules', 'initial' ),
		'noop'     => array( '\LibWeb\ValidatorRules', 'noop' ),
		'store'    => array( '\LibWeb\ValidatorRules', 'store' ),
		'restore'  => array( '\LibWeb\ValidatorRules', 'restore' ),
		'call'     => array( '\LibWeb\ValidatorRules', 'call' ),
		'trim'     => array( '\LibWeb\ValidatorRules', 'trim' ),
		'ltrim'    => array( '\LibWeb\ValidatorRules', 'ltrim' ),
		'rtrim'    => array( '\LibWeb\ValidatorRules', 'rtrim' ),
		'strval'   => array( '\LibWeb\ValidatorRules', 'strval' ),
		'intval'   => array( '\LibWeb\ValidatorRules', 'intval' ),
		'floatval' => array( '\LibWeb\ValidatorRules', 'floatval' ),
		'length'   => array( '\LibWeb\ValidatorRules', 'length' ),
		'date'     => array( '\LibWeb\ValidatorRules', 'date' ),
		'email'    => array( '\LibWeb\ValidatorRules', 'email' ),
		'optional' => array( '\LibWeb\ValidatorRules', 'optional' ),
	);

	/// Get the initial value
	public static function initial( $state, $args ) {
		$state->value = $state->initial;
	}
	/// Noop value
	public static function noop( $state, $args ) {
	}
	/// Store a value
	public static function store( $state, $args ) {
		$name = $args[0];
		$state->store[ $name ] = $state->value;
	}
	/// Restore a value
	public static function restore( $state, $args ) {
		$name = @$args[0];
		$state->value = (!$name) ? $state->initial : $state->store[ $name ];
	}
	/// Call the user function
	public static function call( $state, $args ) {
		$fn  = $args[ 0 ];
		$ret = call_user_func_array( $fn, array_merge( array( $state->value ), array_slice( $args, 1 ) ) );
		if ( $ret === false ) {
			$state->errors[] = array( "name" => "call", "data" => $fn );
			return;
		}
		$state->value = $ret;
	}
	/// Trim the string
	public static function trim( $state, $args ) {
		return self::call( $state, array( 'trim' ) );
	}
	/// Trim the string - Left
	public static function ltrim( $state, $args ) {
		return self::call( $state, array( 'ltrim' ) );
	}
	/// Trim the string - Right
	public static function rtrim( $state, $args ) {
		return self::call( $state, array( 'rtrim' ) );
	}
	/// Get the value as string
	public static function strval( $state, $args ) {
		return self::call( $state, array( 'strval' ) );
	}
	/// Get the value as int
	public static function intval( $state, $args ) {
		return self::call( $state, array( 'intval' ) );
	}
	/// Get the value as float
	public static function floatval( $state, $args ) {
		return self::call( $state, array( 'floatval' ) );
	}
	/// Length
	public static function length( $state, $args ) {
		$options = $args[0];
		if ( is_int( $options ) ) {
			if ( is_int( @$args[1] ) )
				$options = array( "min" => $options, "max" => $args[1] );
			else
				$options = array( "min" => $options );
		}
		if ( !is_array($options) )
			throw new \Exception( "Invalid options for length" );

		$len = strlen( $state->value );
		$min = @$options["min"];
		$max = @$options["max"];
		if ( ($min != null) && ( $len < $min ) ) {
			$state->errors[] = array( "name" => "length", "data" => array( "options" => $options, "value" => $state->value ) );
			return;
		} else if ( ($max != null) && ( $len > $max ) ) {
			$state->errors[] = array( "name" => "length", "data" => array( "options" => $options, "value" => $state->value ) );
			return;
		}
	}
	/// Date
	public static function date( $state, $args ) {
		$format = $args[0];
	    $date   = \DateTime::createFromFormat( $format, $state->value );
		if ( $date === false ) {
			$state->errors[] = array( 'name' => 'date', 'data' => \DateTime::getLastErrors() );
			return;
		}
		$date->setTime( 0, 0, 0 );
		$state->value = $date->format('Y-m-d H:i:s');
	}
	/// Email
	public static function email( $state, $args ) {
		$email = filter_var( $state->value, FILTER_SANITIZE_EMAIL );
		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$state->errors[] = array( "name" => "email", "data" => $email );
			return;
		};
		$state->value = $email;
	}
	
	public static function optional( $state, $args ) {
		$rule = $args[0];
		if ( !$state->value )
			return;
		$rule->validateState( $state );
	}
};

/// A chain of rules 
class ValidatorChain {

	private $rules;
	
	public function __construct( $rules = array() ) {
		$this->rules = $rules;
	}
	/// Add a new rule to this chain
	public function __call( $name, $args ) {
		if ( @!ValidatorRules::$rules[ $name ] )
			throw new \Exception( "Invalid rule ".$name );
		
		$this->rules[] = (object) array(
			"name" => $name,
			"args" => $args
		);
		return $this;
	}
	/// Validate the check
	public function validateCheck( $value ) {
		$state = self::createState( $value );
		return $this->validateState( $state );
	}
	/// Validate the state agains this chain
	public function validateState( $state ) {
		foreach ( $this->rules as $rule ) {
			if ( $state->errors )
				break;
			$this->_applyRule( $state, $rule );
		}
		return $state;
	}
	// Create the state to apply the validations
	public static function createState( $value ) {
		return (object) array(
			"initial"  => $value,
			"value"    => $value,
			"store"    => array(),
			"errors"   => array(),
		);
	}
	// Apply the rule
	private function _applyRule( $state, $rule ) {
		$cb = ValidatorRules::$rules[ $rule->name ];
		call_user_func( $cb, $state, $rule->args );
	}
};

/**
 * Validator class
 */
class Validator {
	private static $defaultValidationRulesCache = null;
	/**
	 * Register a new rule
	 */
	public static function registerRule( $name, $rule ) {
		if ( is_callable( $rule, false, $ruleName ) )
			ValidatorRules::$rules[ $name ] = $ruleName;
	}
	/**
	 * Get the default validation rules
	 */
	public static function defaultValidationRules() {
		if ( !self::$defaultValidationRulesCache ) {
			self::$defaultValidationRulesCache = Validator::trim();
		}
		return self::$defaultValidationRulesCache;
	}
	/**
	 * Validate agains a single rule
	 */
	public static function validate( $value, $rule, $defaultRules = true ) {
		if ( $defaultRules === true )
			$defaultRules = self::defaultValidationRules();

		$state = ValidatorChain::createState( $value );
		if ( $defaultRules && $state->value )
			$state = $defaultRules->validateState( $state );
		$state = $rule->validateState( $state );
		if ( $state->errors )
			throw new ValidatorException( $state );
		return $state->value;
	}
	/**
	 * Validate a full object
	 */
	public static function validateObj( $obj, $rules, $defaultRules = true ) {
		if ( $defaultRules === true )
			$defaultRules = self::defaultValidationRules();
		$assoc     = (array) $obj;
		$validated = array();
		$errors    = array();
		foreach ( $rules as $key => $rule ) {
			if ( $key && ($key[strlen($key)-1] === "?" ) ) {
				$key  = substr( $key, 0, -1 );
				$rule = Validator::optional( $rule );
			}

			$skipDefault = false;
			if ( $key && ($key[0] === "!" ) ) {
				$key  = substr( $key, 1 );
				$skipDefault = true;
			}

			$state = ValidatorChain::createState( @$assoc[ $key ] );
			if ( !$skipDefault && $defaultRules && $state->value )
				$state = $defaultRules->validateState( $state );
			$state = $rule->validateState( $state );
			if ( $state->errors )
				$errors[ $key ] = $state->errors;
			else
				$validated[ $key ] = $state->value;
		}
		if ( $errors ) {
			$errorState = array( "value" => (object) $obj, "errors" => $errors );
			throw new ValidatorException( $errorState, array_keys( $errors ) );
		}
		return (object) $validated;
	}

	
	/// Create a chain with the given validator
	public static function __callStatic( $name, $args ) {
		$chain = new ValidatorChain;
		$chain->__call( $name, $args );
		return $chain;
	}
	
};