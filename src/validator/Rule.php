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
		if ( is_array( $rule ) ) {
			$normalized = array();
			$special    = array();
			foreach ( $rule as $key => $value ) {
				// Skip keys beginning with $ unless double $$
				if ( @$key[0] === '$' ) {
					$key = substr( $key, 1 );
					if ( @$key[0] !== '$' ) {
						$special[ $key ] = $value;
						continue;
					}
				}

				// Normalize the rule
				$value = self::normalize( $value );

				// Check optional and skippable flag
				$childFlags = 0;
				$keyLen = strlen( $key );
				if ( @$key[$keyLen-1] === '?' ) {
					if ( @$key[$keyLen-2] === '?' ) {
						$childFlags = self::FLAG_SKIPPABLE  | self::FLAG_OPTIONAL;
						$key = substr( $key, 0, $keyLen - 2 );
					} else {
						$childFlags = self::FLAG_OPTIONAL;
						$key = substr( $key, 0, $keyLen - 1 );
					}
				}

				// Normalize
				if ( $childFlags )
					$value = $value->withFlags( $childFlags );

				// Save the key
				$normalized[ $key ] = $value;
			}
			
			$rule = new rule\ObjectRule( $normalized, $special );
		}
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

	// Flag
	protected $flags_ = 0;
	private   $dependencies_ = array();
};