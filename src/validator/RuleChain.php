<?php
namespace LibWeb\validator;

class RuleChain extends Rule {

	private $rules = array();
	
	public function __call( $method, $args ) {
		$rule = RuleSet::get( $method, $args );
		$rule->root_ = $this->root_ ?: $this;
		if ( method_exists( $rule, '_setup' ) )
			$rule->_setup();
		$this->rules[] = $rule;
		$this->dependencies_ = $this->dependencies_ + $rule->dependencies_;
		return $this;
	}

	public function _clone() {
		$chain = new RuleChain();
		foreach ( $this->rules as $rule ) {
			$ruleClone = $rule->clone();
			if ( $ruleClone->root_ === $this )
				$ruleClone->root_ = $chain;
			$chain->rules[]   = $ruleClone;
		}
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