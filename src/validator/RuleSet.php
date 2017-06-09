<?php
namespace LibWeb\validator;

class InlineRule extends Rule {

	private $method;
	private $args;

	public function __construct( $method, $args = null ) {
		$this->method = $method;
		$this->args   = $args;
	}

	public function apply( $state ) {
		$value = $state->value;
		if ( $this->args ) {
			$args = $this->args;
			array_unshift( $args, $value );
			call_user_func_array( $this->method, $args );
		} else {
				
		}
		
	}

	
};

class RuleSet {

	private static $rules = array();

	
};