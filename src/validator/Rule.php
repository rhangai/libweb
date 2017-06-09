<?php
namespace LibWeb\validator;

/**
 *
 */
abstract class Rule {

    const FLAG_OPTIONAL = 0x01;
	/// Apply a rule
	abstract public function apply( $state );
	/**
	 * Validate the state
	 */
	public static function validateState( $rule, $state, $flags = 0 ) {
		if ( is_array( $rule ) )
		    return self::validateStateObject( $rule, $state, $flags );

		if ( $state->value === null ) {
			if (( $flags & self::FLAG_OPTIONAL ) === 0 )
				$state->setError();
			return;
		}
		$rule->apply( $state );
	}
	/**
	 * Validate the state of an array
	 */
	public static function validateStateObject( $rules, $state, $flags = 0 ) {
		$result = array();
		$values = (array) $state->value;
		foreach ( $rules as $key => $rule ) {
			$childFlags = 0;
		    $keyLen = strlen( $key );
			if ( $key[ $keyLen - 1 ] === '?' ) {
			    $childFlags |= self::FLAG_OPTIONAL;
				$key         = substr( $key, 0, $keyLen - 1 );
			}
			
			$childState = new State( @$values[$key], $key, $state );
			self::validateState( $rule, $childState, $childFlags );
			$result[ $key ] = $childState->value;
		}
		$state->value = (object) $result;
	}
};