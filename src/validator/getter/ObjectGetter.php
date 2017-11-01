<?php
namespace LibWeb\validator\getter;

class ObjectGetter implements Getter {
	private $obj_;
	public function __construct( $obj ) {
		$this->obj_ = $obj;
	}
	public function get( $key ) {
		return $this->obj_->validatorGet( $key );
	}
};
