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
	
	public function __construct( $rules ) {
		$normalized = array();
		$special    = array();
		foreach ( $rules as $key => $value ) {
			// Skip keys beginning with $ unless double $$
			if ( @$key[0] === '$' ) {
				$key = substr( $key, 1 );
				if ( @$key[0] !== '$' ) {
					$special[ $key ] = $value;
					continue;
				}
			}

			// Normalize the rule
			$value = Rule::normalize( $value );

			// Check optional and skippable flag
			$childFlags = 0;
			$keyLen = strlen( $key );
			if ( @$key[$keyLen-1] === '?' ) {
				if ( @$key[$keyLen-2] === '?' ) {
					$childFlags = self::FLAG_SKIPPABLE  | self::FLAG_OPTIONAL;
					$key = substr( $key, 0, $keyLen - 2 );
				} else {
					$childFlags = self::FLAG_OPTIONAL;
					$key = substr( $key, 0, $keyLen - 1 );
				}
			}

			// Normalize
			if ( $childFlags )
				$value = $value->withFlags( $childFlags );

			// Save the key
			$normalized[ $key ] = $value;
		}
		$this->rules_ = $normalized;
		$this->specialRules_ = $special;
	}
	public function _clone() {
		return new ObjectRule( $this->rules_, $this->specialRules_ );
	}
	public function apply( $state ) {
		$getter = Rule::createGetterFor( $state->value );
		
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

		if ( isset( $this->specialRules_['after'] ) ) {
			Rule::validateState( $state, $this->specialRules_['after'] );
		}
	}

	public function getRule( $field ) {
		return $this->rules_[ $field ];
	}

};
