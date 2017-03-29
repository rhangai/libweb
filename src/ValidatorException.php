<?php namespace LibWeb;

class ValidatorException extends \Exception {

	private $state;
	private $fields;
	
	public function __construct( $state, $fields = null ) {
		$this->state  = $state ? ((object) $state) : null;
		$this->fields = $fields;
		$fields_str = '';
		if ( $fields ) {
			$fields_str = ' on fields '.implode( ',', array_map(function($f) { return '"'.$f.'"'; }, $fields ) );
		}

		$error_message = '';
		if ( $this->state && $this->state->errors ) {
			$error_message = "\n".json_encode( $this->state->errors, JSON_PRETTY_PRINT );
		}
		parent::__construct( "Validation error".$fields_str.$error_message );
	}

	public function serializeAPI() {
		return $this->state ? $this->state->errors : null;
	}
	
};
