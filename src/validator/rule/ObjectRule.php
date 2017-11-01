<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\State;
use LibWeb\validator\Rule;
use LibWeb\validator\getter\NullGetter;
use LibWeb\validator\getter\ArrayGetter;
use LibWeb\validator\getter\ObjectGetter;

class ObjectRule extends Rule {
	private $rules_;
	private $specialRules_;
	public function __construct( $rules, $special ) {
		$this->rules_ = $rules;
		$this->specialRules_ = $special;
	}
	public function _clone() {
		return new ObjectRule( $this->rules_, $this->specialRules_ );
	}
	public function apply( $state ) {
		$getter = null;
		if ( !$state->value ) {
			$getter = new NullGetter;
		} else if ( is_array( $state->value ) ) {
			$getter = new ArrayGetter( $state->value );
		} else if ( is_object( $state->value ) ) {
			if ( $state->value instanceof \stdClass )
				$getter = new ArrayGetter( ( array ) $state->value );
			else  if ( method_exists( $state->value, 'validatorGet' ) )
				$getter = new ObjectGetter( $state->value );
			else
				throw new \InvalidArgumentException( "Cannot validate complex object without a validatorGet method." );
		}
		if ( !$getter )
			throw new \InvalidArgumentException( "Cannot validate." );
		
		$result = array();
		foreach ( $this->rules_ as $key => $rule ) {
			$childState = new State( $getter->get( $key ), $key, $state );
			Rule::validateState( $childState, $rule );
			$state->mergeErrors( $childState->errors() );
			if ( ( $childState->value === null ) && ( $rule->testFlag( Rule::FLAG_SKIPPABLE ) ) )
				continue;
			$result[$key] = $childState->value;
		}
		
		$state->value = (object) $result;
	}
};
