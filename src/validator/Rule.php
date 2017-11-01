<?php
namespace LibWeb\validator;

class RuleGetterNull {
	public function get( $key ) { return null; }
};
class RuleGetterArray {
	public $ary;
	public function __construct( $ary ) { $this->ary = $ary; }
	public function get( $key ) { return @$this->ary[$key]; }
};
class RuleGetterObject {
	public $obj;
	public function __construct( $obj ) { $this->obj = $obj; }
	public function get( $key ) { return $this->obj->validatorGet( $key ); }
};
/**
 *
 */
abstract class Rule {

	const FLAG_OPTIONAL  = 0x01;
	const FLAG_SKIPPABLE = 0x02;
	/// Apply a rule
	abstract public function apply( $state );
	/**
	 * Validate the state
	 */
	public static function validateState( $rule, $state, $flags = 0 ) {
		if ( ( $state->value === null ) || ( $state->value === '' ) ) {
			if (( $flags & self::FLAG_OPTIONAL ) === 0 )
				$state->setError( "Field is not optional" );
			$state->value = null;
			return;
		}
		if ( is_array( $rule ) )
			self::validateStateObject( $rule, $state, $flags );
		else
			$rule->apply( $state );
	}
	/**
	 * Validate the state of an array
	 */
	public static function validateStateObject( $rules, $state, $flags = 0 ) {
		$result = array();
		$values = $state->value;
		$getter = null;
		if ( !$values )
			$getter = new RuleGetterNull;
		else if ( is_array( $values ) )
			$getter = new RuleGetterArray( $values );
		else if ( is_object( $values ) ) {
			if ( $values instanceof \stdClass )
				$getter = new RuleGetterArray( (array) $values );
			else if ( is_callable( array( $values, "validatorGet" ) ) )
				$getter = new RuleGetterObject( $values );
		}
		if ( !$getter ) {
			$state->setError( "Invalid object to validate" );
			return;
		}

		foreach ( $rules as $key => $rule ) {
			//
			if ( @$key[0] === '$' )
				continue;
			
			// Check for flags
			$childFlags = 0;
			$keyLen = strlen( $key );
			if ( $key[ $keyLen - 1 ] === '?' ) {
			    if ( $key[ $keyLen - 2 ] === '?' ) {
					$childFlags |= self::FLAG_SKIPPABLE | self::FLAG_OPTIONAL;
					$key		 = substr( $key, 0, $keyLen - 2 );
				} else {
					$childFlags |= self::FLAG_OPTIONAL;
					$key		 = substr( $key, 0, $keyLen - 1 );
				}
			} 
			
			$childState = new State( $getter->get( $key ), $key, $state );
			self::validateState( $rule, $childState, $childFlags );
			if ( $childFlags & self::FLAG_SKIPPABLE ) {
				if ( $childState->value === null )
					continue;
			}
			$result[ $key ] = $childState->value;
		}
		$state->value = (object) $result;
		if ( @$rules['$after'] )
			self::validateState( $rules['$after'], $state, $flags );
	}
};