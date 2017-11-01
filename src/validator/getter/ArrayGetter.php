<?php
namespace LibWeb\validator\getter;

class ArrayGetter implements Getter {
	public $ary;
	public function __construct( $ary ) {
		$this->ary = $ary;
	}
	public function get( $key ) {
		return @$this->ary[$key];
	}
};
