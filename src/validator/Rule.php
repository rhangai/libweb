<?php
namespace LibWeb\validator;

use MJS\TopSort\Implementations\StringSort;

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
	const FLAG_ALWAYS    = 0x04;
	
	/// Clone this rule
	final public function clone() {
		$clone = $this->_clone();
		$clone->root_         = $this->root_;
		$clone->flags_        = $this->flags_;
		$clone->dependencies_ = $this->dependencies_;
		return $clone;
	}
	/// Apply a rule
	abstract public function _clone();
	abstract public function apply( $state );
	/**
	 * Clona com a flag
	 */
	public static function withFlags( $rule, $flags, $set = false ) {
		if ( is_array( $rule ) )
			$rule = new rule\ObjectRule( $rule );
		$other = $rule->clone();
		$other->flags_ = ( $set ? $flags : ($rule->flags_ | $flags) );
		return $other;
	}
	/**
	 * Validate the state
	 */
	public static function validateState( $rule, $state, $flags = null ) {
		if ( is_array( $rule ) )
			$rule = new rule\ObjectRule( $rule );
		if ( $flags === null )
			$flags = $rule->flags_;

		if ( ( $flags & self::FLAG_ALWAYS ) === 0 ) {
			if ( ( $state->value === null ) || ( $state->value === '' ) ) {
				if (( $flags & self::FLAG_OPTIONAL ) === 0 )
					$state->setError( "Field is not optional" );
				$state->value = null;
				return;
			}
		}
		$rule->apply( $state );
	}
	/**
	 * Validate the state of an array
	 */
	public static function validateStateObject( $rules, $state, $flags = null ) {
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


		// Normalize rules
		$normalizedRules = array();
		foreach ( $rules as $key => $rule ) {
			// Normalize rules
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
			$normalizedRules[$key] = self::withFlags( $rule, $childFlags );
		}

		//
		$ruleSorter = new StringSort();
		foreach ( $normalizedRules as $key => $rule ) {
			$ruleSorter->add( $key, $rule->dependencies_ );
		}
		$sortedRules = $ruleSorter->sort();
		

		$result = (object) array();
		$state->rules = $normalizedRules;
		$state->value = $result;
		foreach ( $sortedRules as $key ) {
			$rule = $normalizedRules[$key];
			$childState = new State( $getter->get( $key ), $key, $state );
			self::validateState( $rule, $childState );
			if ( $rule->flags_ & self::FLAG_SKIPPABLE ) {
				if ( $childState->value === null )
					continue;
			}
			$result->{$key} = $childState->value;
		}
		if ( @$rules['$after'] )
			self::validateState( $rules['$after'], $state, $flags );
	}

	public function dependsOn( $field ) {
		$this->dependencies_[] = $field;
		return $this;
	}

	public function getRoot() { return $this->root_ ?: $this; }

	// Flag
	public $root_;
	public $flags_ = 0;
	public $dependencies_ = array();
};