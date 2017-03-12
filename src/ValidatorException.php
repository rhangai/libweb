<?php namespace LibWeb;

class ValidatorException extends \Exception {

	private $state;
	private $fields;
	
	public function __construct( $state, $fields = null ) {
		$this->state  = $state;
		$this->fields = $fields;
		$fields_str = '';
		if ( $fields ) {
			$fields_str = ' on fields '.implode( ',', array_map(function($f) { return '"'.$f.'"'; }, $fields ) );
		}
		parent::__construct( "Validation error".$fields_str );
	}

};