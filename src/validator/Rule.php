<?php
namespace LibWeb\validator;

use MJS\TopSort\Implementations\StringSort;

/**
 *
 */
abstract class Rule {

	const FLAG_OPTIONAL  = 0x01;
	const FLAG_SKIPPABLE = 0x02;
	const FLAG_ALWAYS    = 0x04;

	// Construct
	public function __construct( $flags = 0 ) {
		$this->flags_ = $flags;
	}
	/**
	 * Normalize the rule
	 */
	public static function tryNormalize( $rule ) {
		if ( is_array( $rule ) )
			$rule = new rule\ObjectRule( $rule );
		if ( !$rule instanceof Rule )
			return null;
		return $rule;
	}
	/**
	 * Normalize the rule
	 */
	public static function normalize( $rule ) {
		$rule = self::tryNormalize( $rule );
		if ( !$rule )
			throw new \InvalidArgumentException( "Expecting Rule" );
		return $rule;
	}
	/**
	 * Validate the state
	 */
	public static function validateState( $state, $rule ) {
		$rule = self::normalize( $rule );
		if ( !$rule->testFlag( Rule::FLAG_ALWAYS ) ) {
			if ( $state->value === null ) {
				if ( !$rule->testFlag( Rule::FLAG_OPTIONAL ) )
					$state->setError( "Rule is not optional" );
				return;
			}
		}
		$state->setCurrentRuleInternal( $rule );
		$rule->apply( $state );
	}
	/**
	 *
	 */
	public function dependsOn( $field ) {
		$this->dependencies_[] = $field;
		return $this;
	}
	/**
	 *
	 */
	public function withFlags( $flags, $set = false ) {
		$clone = $this->clone();
		$clone->flags_ = ( $set ? $flags : ( $clone->flags_ | $flags ) );
		return $clone;
	}
	public function testFlag( $flag ) {
		return ($this->flags_ & $flag) === $flag;
	}
	public function getFlags() {
		return $this->flags_;
	}

	
	/// Clone this rule
	final public function clone() {
		$clone = $this->_clone();
		$clone->flags_        = $this->flags_;
		$clone->dependencies_ = $this->dependencies_;
		return $clone;
	}
	/// Apply a rule
	abstract public function _clone();
	abstract public function apply( $state );


	public static function createGetterFor( $value ) {
		$getter = null;
		if ( !$value ) {
			$getter = new getter\NullGetter;
		} else if ( is_array( $value ) ) {
			$getter = new getter\ArrayGetter( $value );
		} else if ( is_object( $value ) ) {
			if ( $value instanceof \stdClass )
				$getter = new getter\ArrayGetter( ( array ) $value );
			else  if ( method_exists( $value, 'validatorGet' ) )
				$getter = new getter\ObjectGetter( $value );
			else if ( $value instanceof getter\Getter )
				$getter = $value;
			else
				throw new \InvalidArgumentException( "Cannot validate complex object without a validatorGet method." );
		}
		if ( !$getter )
			throw new \InvalidArgumentException( "Cannot validate." );
		return $getter;
	}

	// Flag
	protected $flags_ = 0;
	private   $dependencies_ = array();
};