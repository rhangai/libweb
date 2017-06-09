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
	
	public function __construct( $format, $out = null ) {
		$this->format  = $format;
		$this->out     = $out;
	}

	public function apply( $state ) {
		$value = $state->value;
		if ( is_string( $value ) ) {
			$value = \DateTime::createFromFormat( $this->format, $value );
			if ( $value === false ) {
				$state->setError( "Invalid date" );
				return;
			}
			if ( $value->format( $this->format ) !== $state->value ){
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
	
};
