<?php
namespace LibWeb\validator;

class RuleChain extends Rule {

	private $rules = array();
	
	public function __call( $method, $args ) {
		$rule = RuleSet::get( $method, $args );
		$this->rules[] = $rule;
		$this->dependencies_ = $this->dependencies_ + $rule->dependencies_;
		return $this;
	}

	public function _clone() {
		$chain = new RuleChain();
		foreach ( $this->rules as $rule )
			$chain->rules[] = $rule->clone();
		return $chain;
	}

	public function apply( $state ) {
		foreach ( $this->rules as $rule ) {
			$rule->apply( $state );
			if ( $state->error )
				break;
		}
	}
};