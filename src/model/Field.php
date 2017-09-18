<?php

namespace LibWeb\model;

class Field {

	private $descriptor_;
	public function __construct( $descriptor ) {
		$this->descriptor_ = (object) $descriptor;
	}

	public function __get( $name ) {
		return @$this->descriptor_->{$name};
	}
	
	public function isRequired() {
		return @$this->descriptor->required ?: false;
	}

	
};