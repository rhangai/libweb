<?php
namespace LibWeb;

class APIException extends \Exception {

	private $data_;
	
	public function __construct( $data, $code = 500, $msg = "" ) {
		if ( is_string( $code ) ) {
			$msg  = $code;
			$code = 500;
		}
		parent::__construct( $msg, $code );
		$this->data_ = $data;
	}

	public function serializeAPI() {
		return $this->data_;
	}
};