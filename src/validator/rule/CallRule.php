<?php
namespace LibWeb\validator\rule;

class CallRule extends InlineRule {
	
	public function __construct( $method ) {
		$args = func_get_args();
		array_shift( $args );
		parent::__construct( $method, $args );
	}
	
};
