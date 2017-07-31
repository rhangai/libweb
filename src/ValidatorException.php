<?php namespace LibWeb;

class ValidatorException extends \Exception {

	private $errors;
	private $fields;
	
	public function __construct( $state, $errors ) {
		$this->state   = $state;
		$this->errors  = $errors;

		$msg = "Invalid fields: \n";
		foreach ( $errors as $field ) {
			if ( $field->key ) {
				$msg .= "    ".implode(".", $field->key)." => ".( $field->error === true ? "Error" : $field->error )."\n";
			} else {
				$msg .= "    ".( $field->error === true ? "Error" : $field->error )."\n";
			}
		}
		
		parent::__construct( $msg );
	}

	public function serializeAPI() {
		return $this->state ? array( "type" => "validator", "fields" => $this->errors ) : null;
	}
	
};
