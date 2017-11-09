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
		$parent = $state->getParent();
		if ( !$parent )
			throw new \InvalidArgumentException( "Can only be used inside an ObjectRule" );
		$parentRule = $parent->getCurrentRule();
		if ( !($parentRule instanceof rule\ObjectRule) )
			throw new \InvalidArgumentException( "Can only be used inside an ObjectRule" );

		$getter = Rule::createGetterFor( $parent->value );
		Rule::validateState( $state, $parentRule->getRule( $field ) );
		if ( $getter->get( $field ) != $state->value )
			$state->setError( new RuleException( "Field must be the same as ".$field ) );
	}
	public static function same__setup( $rule, $field ) {
		$rule->dependsOn( $field );
	}
	
	public static function mergeIf( $state, $fields, $rules, $rulesElse = null ) {
		$merge = true;
		foreach ( $fields as $key => $value ) {
			if ( is_int( $key ) && !isset( $state->value->{$key} ) ) {
				$merge = false;
			} else if ( @$state->value->{$key} !== $value ) {
				$merge = false;
			}
			if ( !$merge )
				break;
		}

		$rules = $merge ? $rules : $rulesElse;
		if ( !$rules )
			return;


		$childvalue = new getter\MergeGetter([ Rule::createGetterFor( $state->getInitial() ), Rule::createGetterFor( $state->value ) ]);
		$childstate = new State( $childvalue );
		Rule::validateState( $childstate, $rules );
		foreach ( $childstate->value as $key => $value )
			$state->value->{ $key } = $value;

		$errors = $childstate->errors();
		$state->mergeErrors( $errors );
	}
	public static function mergeIf__setup( $rule, $fields, $rules ) {
		foreach ( $fields as $key => $value )
			$rule->dependsOn( is_int( $key ) ? $value : $key );
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