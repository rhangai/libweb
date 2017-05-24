<?php
namespace LibWeb;

class APIException extends \Exception {

	private $data_;
	private $type_;
	
	public function __construct( $data, $type = null, $msg = "", $code = 500 ) {
		parent::__construct( $msg, $code );
		$this->data_ = $data;
		$this->type_ = $type;
	}
	
	public function getType() {
		return $this->type_;
	}

	public function serializeAPI() {
		return $this->data_;
	}
};