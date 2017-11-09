<?php
namespace LibWeb\validator;

/**
 * Simple Rule Exception
 */
class RuleException extends \Exception {

	public static function createWithValue( $message, $value ) {
		$message .= " Passed ".gettype( $value ).": ".$value;
		return new static( $message );
	}
}

