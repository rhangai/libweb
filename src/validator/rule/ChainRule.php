<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;
use LibWeb\validator\RuleSet;

class ChainRule extends Rule {

	private $rules_ = array();
	
	public function __call( $method, $args ) {
		$rule = RuleSet::get( $method, $args );
		$this->rules_[] = $rule;
		return $this;
	}

	public function _clone() {
		$chain = new ChainRule();
		foreach ( $this->rules_ as $rule )
			$chain->rules_[] = $rule->clone();
		return $chain;
	}
	
	public function apply( $state ) {
		foreach ( $this->rules_ as $rule ) {
			$rule->apply( $state );
			if ( $state->isDone() )
				break;
		}
	}
};