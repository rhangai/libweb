<?php namespace LibWeb;

use LibWeb\APIException;

class ValidatorException extends APIException {

	private $errors;
	private $fields;
	private $serializable;
	private $stack;
	
	public function __construct( $state, $errors, $serializable = false ) {
		$serializable = !!$serializable;
		
		$this->state        = $state;
		$this->errors       = $errors;
		$this->serializable = $serializable;
		$this->stack        = $stack;

		$msg = "Invalid fields: \n";
		foreach ( $errors as $field ) {
			if ( $field->key ) {
				$msg .= "    ".implode(".", $field->key)." => ".( $field->error === true ? "Error" : $field->error )."\n";
			} else {
				$msg .= "    ".( $field->error === true ? "Error" : $field->error )."\n";
			}
		};
		parent::__construct( $this->serializable ? $this->errors : null, $this->serializable ? "validator" : null, $msg );

		$stack = $this->getTrace();
		$base = __DIR__;

		for ( $i = 0, $len = count($stack); $i<$len; ++$i ) {
			$item = $stack[ $i ];
			if ( @$item["file"] === null || @$item["line"] === null )
				continue;

				
			$file = $item[ "file" ];
			if ( substr( $file, 0, strlen( $base ) ) === $base )
				continue;
			$this->stack = $item;
			break;
		}

	}

	public function serializeFile() {
		if ( $this->stack === null )
			return array( "file" => parent::getFile(), "line" => parent::getLine() );
		return $this->stack;
	}
};
