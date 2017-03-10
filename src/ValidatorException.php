<?php namespace LibWeb;

class ValidatorException extends \Exception {

	private $state;
	private $fields;
	
	public function __construct( $state, $fields = null ) {
		$this->state = $state;
		parent::__construct( "Validation error" );
	}

};