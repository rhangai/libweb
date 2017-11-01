<?php
namespace LibWeb\validator\rule;

use LibWeb\validator\Rule;

class ValidatorDateTime extends \DateTime {
	private $out;
	public function setOutputFormat( $format ) {
		$this->out = $format;
	}
	public function __toString() {
		$format = $this->out ?: "Y-m-d H:i:s";
		return $this->format( $format );
	}
}

/// DateTime rule
class DateTimeRule extends Rule {
	
	private $format;
	private $out;
	
	public function __construct( $format = null, $out = null ) {
		if ( $format === null )
		    $format = array( "Y-m-d H:i:s", "Y-m-d" );
		$this->format  = $format;
		$this->out     = $out;
	}

	public function _clone() {
		return new DateTimeRule( $this->format, $this->out );
	}

	public function apply( $state ) {
		$value = $state->value;
		if ( is_string( $value ) ) {
			$value = self::tryParse( $value, $this->format );
			if ( $value === false ) {
				$state->setError( "Invalid date" );
				return;
			}
		}
		if ( !( $value instanceof \DateTime ) ) {
			$state->setError( "Invalid date object" );
			return;
		}
		$newtime = new ValidatorDateTime;
		$newtime->setTimestamp( $value->getTimestamp() );
		$newtime->setOutputFormat( $this->out );
		$state->value = $newtime;
	}
	
	private static function tryParse( $value, $format ) {
		if ( is_array( $format ) ) {
			foreach ( $format as $item ) {
				$result = self::tryParse( $value, $item );
				if ( $result !== false )
					return $result;
			}
			return false;
		}
		
		$result = \DateTime::createFromFormat( '!'.$format, $value );
		if ( $result === false )
			return false;
		if ( $result->format( $format ) !== $value )
			return false;
		return $result;
	}
	
};
