<?php namespace LibWeb;

class ValidatorRules {
	
	public static $rules = array(
		'call'     => array( '\LibWeb\ValidatorRules', 'call' ),
		'email'    => array( '\LibWeb\ValidatorRules', 'email' ),
		'optional' => array( '\LibWeb\ValidatorRules', 'optional' ),
	);
	
	public static function call( $state, $args ) {
		$fn  = $args[ 0 ];
		$ret = call_user_func_array( $fn, array_merge( array( $state->value ), array_slice( $args, 1 ) ) );
		if ( $ret === false ) {
			$state->errors[] = array( "name" => "call", "data" => $fn );
			return;
		}
		$state->value = $ret;
	}

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
		$newState = $rule->validateCheck( $state->value );
		
		$state->errors = array_merge( $state->errors, $newState->errors );
		$state->value  = $newState->value;
	}
};

class ValidatorChain {

	private $rules;
	
	public function __construct( $rules = array() ) {
		$this->rules = $rules;
	}

	public function __call( $name, $args ) {
		if ( @!ValidatorRules::$rules[ $name ] )
			throw new \Exception( "Invalid rule ".$name );
		
		$this->rules[] = (object) array(
			"name" => $name,
			"args" => $args
		);
	}

	public function validateCheck( $value ) {
		$state = (object) array(
			"initial"  => $value,
			"value"    => $value,
			"errors"   => array(),
		);
		foreach ( $this->rules as $rule ) {
			$this->_applyRule( $state, $rule );
			if ( $state->errors )
				break;
		}
		return $state;
	}

	private function _applyRule( $state, $rule ) {
		$cb = ValidatorRules::$rules[ $rule->name ];
		call_user_func( $cb, $state, $rule->args );
	}
};

class Validator {

	public static function registerRule( $name, $rule ) {
		if ( is_callable( $rule, false, $ruleName ) )
			ValidatorRules::$rules[ $name ] = $ruleName;
	}

	public static function validate( $value, $rule ) {
		$state = $rule->validateCheck( $value );
		if ( $state->errors )
			throw new ValidatorException( $state );
		return $state->value;
	}

	public static function validateObj( $obj, $rules ) {
		$assoc     = (array) $obj;
		$validated = array();
		$errors    = array();
		foreach ( $rules as $key => $rule ) {
			if ( $key && ($key[strlen($key)-1] === "?" ) ) {
				$key  = substr( $key, 0, -1 );
				$rule = Validator::optional( $rule );
			}
			
			$state = $rule->validateCheck( @$assoc[ $key ] );
			if ( $state->errors )
				$errors[ $key ] = $state->errors;
			else
				$validated[ $key ] = $state->value;
		}
		if ( $errors )
			throw new ValidatorException( $errors );
		return $validated;
	}

	public static function __callStatic( $name, $args ) {
		$chain = new ValidatorChain;
		$chain->__call( $name, $args );
		return $chain;
	}
	
};