<?php
use LibWeb\api\Request;

\LibWeb\Validator::addRuleRaw( 'file', function( $state, $multiple = false ) {
	$parent = $state->getParent();
	if ( !$parent ) {
		$state->addError( "Invalid parent. Must be a Request instance" );
		return;
	}
	$parent = $parent->value;
	if ( ( !$parent ) || !($parent instanceof Request ) ) {
		$state->addError(  "Invalid parent. Must be a Request instance" );
		return;
	}

	// Check for state
	if ( $multiple ) {
		if ( !$state->value )
			$state->value = array();
		else if ( !is_array( $state->value ) )
			$state->value = array( $state->value );
	} else {
		if ( is_array( $state->value ) )
			$state->value = @$state->value[ 0 ];
	}
	
});