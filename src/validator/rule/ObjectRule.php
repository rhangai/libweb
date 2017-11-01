<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;

class ObjectRule extends Rule {
	private $rule;
	public function __construct( $rule ) {
		$this->rule = $rule;
	}
	public function _clone() {
		return new ObjectRule( $this->rule );
	}
	public function apply( $state ) {
		Rule::validateStateObject( $this->rule, $state );
	}
};
