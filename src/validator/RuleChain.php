<?php
namespace LibWeb\validator;

class RuleChain extends Rule {

	private $rules = array();
	
	public function __call( $method, $args ) {
		$this->rules[] = RuleSet::get( $method, $args );
	}

	public function apply( $state ) {
		foreach ( $this->rules as $rule ) {
			$rule->apply( $state );
			if ( $state->error )
				break;
		}
	}
};