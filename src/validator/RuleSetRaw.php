<?php
namespace LibWeb\validator;

class RuleSetRaw extends Rule {

	private $method;
	private $args;

	public function __construct( $method, $args ) {
		$this->method = $method;
		$this->args   = $args;
	}

	public function _setup() {
		$method = $this->method;
		if ( is_string( $method ) && is_callable( array( __CLASS__, $method.'__setup' ) ) ) {
			$setupArgs = $this->args;
			array_unshift( $setupArgs, $this );
			call_user_func_array( array( __CLASS__, $method.'__setup' ), $setupArgs );
		}
	}

	public function _clone() {
		return new RuleSetRaw( $this->method, $this->args );
	}
	
	public function apply( $state ) {
		$args = $this->args;
		array_unshift( $args, $state );
		call_user_func_array( array( __CLASS__, $this->method ), $args );
	}

	public static function same( $state, $field ) {
		Rule::validateState( $state, $state->getParent()->rules[ $field ] );
		if ( @$state->getParent()->value->{$field} != $state->value )
			$state->setError( new \Exception( "Field must be the same as ".$field ) );
	}
	public static function same__setup( $rule, $field ) {
		$rule->dependencies_[] = $field;
	}
	
	public static function ifField( $state, $field, $rules ) {
		if ( @$state->getParent()->value->{$field} !== null ) {
			Rule::validateState( $state, $rules );
		} else
			$state->value = null;
	}
	public static function ifField__setup( $rule, $field, $rules ) {
		$field = (array) $field;
		foreach ( $field as $dep )
			$rule->dependencies_[] = $dep;
		$rule->getRoot()->flags_ |= Rule::FLAG_ALWAYS;
	}


	public static function oneOf( $state, $values ) {
		$value = $state->value;
		foreach ( $values as $testValue ) {
			$rule = Rule::tryNormalize( $testValue );
			if ( !$rule ) {
				if ( $testValue === $value )
					return;
			} else {
				$cloneState = clone $state;
				Rule::validateState( $cloneState, $rule );
				if ( !$cloneState->errors() ) {
					$state->value = $cloneState->value;
					return;
				}
			}
		}
		$state->setError( new \Exception( "Must be one of the validators passed" ) );
	}
	

	
};