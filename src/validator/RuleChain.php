<?php
namespace LibWeb\validator;

class RuleChain extends Rule {

	private $rules = array();
	
	public function __call( $method, $args ) {
		$this->rules[] = RuleSet::get( $method, $args );
		return $this;
	}

	public function _clone() {
		$chain = new RuleChain();
		foreach ( $this->rules as $rule )
			$chain->rules[] = $rule->clone();
		return $chain;
	}

	public function optional() {
		
	}

	public function apply( $state ) {
		foreach ( $this->rules as $rule ) {
			$rule->apply( $state );
			if ( $state->error )
				break;
		}
	}
};