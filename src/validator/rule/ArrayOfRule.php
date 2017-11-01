<?php
namespace LibWeb\validator\rule;
use LibWeb\validator\Rule;
use LibWeb\validator\State;

class ArrayOfRule extends Rule {

	private $rule;

	public function __construct( $rule ) {
		$this->rule = $rule;
	}

	public function apply( $state ) {
		$result = array();
		$values = ( array ) $state->value;
		foreach ( $values as $key => $v ) {
			$childState = new State( $v, $key, $state );
			Rule::validateState( $this->rule, $childState );
			$result[$key] = $childState->value;
		}
	    $state->value = $result;
	}

	// Rule
	public function _clone() {
		return new ArrayOfRule( $this->rule );
	}

	
};
