<?php namespace LibWeb;

use LibWeb\APIException;

class ValidatorException extends APIException {

	private $errors;
	private $fields;
	private $serializable;
	
	public function __construct( $state, $errors, $serializable = false ) {
		$this->state        = $state;
		$this->errors       = $errors;
		$this->serializable = !!$serializable;

		$msg = "Invalid fields: \n";
		foreach ( $errors as $field ) {
			if ( $field->key ) {
				$msg .= "    ".implode(".", $field->key)." => ".( $field->error === true ? "Error" : $field->error )."\n";
			} else {
				$msg .= "    ".( $field->error === true ? "Error" : $field->error )."\n";
			}
		};
		parent::__construct( $this->serializable ? $this->errors : null, $this->serializable ? "validator" : null, $msg );
	}	
};
