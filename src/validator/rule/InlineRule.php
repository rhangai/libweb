<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;

class InlineRuleValue {
	public $value;
	public function __construct( $value ) {
		$this->value = $value;
	}
}

class InlineRule extends Rule {

	private $method;
	private $args;

	public function __construct( $method, $args = null ) {
		$this->method = $method;
		$this->args   = $args;
	}

	public function apply( $state ) {
		$value = $state->value;
		try {
			if ( $this->args ) {
				$args = $this->args;
				array_unshift( $args, $value );
				$ret = call_user_func_array( $this->method, $args );
			} else {
				$ret = call_user_func( $this->method, $value );				
			}
		} catch( \Exception $e ) {
			$ret = $e;
		}
		if ( $ret === false )
			$state->setError();
		else if ( $ret instanceof \Exception )
			$state->setError( $ret );
		else if ( $ret instanceof InlineRuleValue )
			$state->value = $ret->value;
		else if ( $ret && $ret !== true )
			$state->value = $ret;
	}

	
};
