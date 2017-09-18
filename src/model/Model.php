<?php

namespace LibWeb\model;

class Model {

	private $descriptor_;
	private $fields_;
	
	public function __construct( $descriptor ) {
		$descriptor = (object) $descriptor;
		
		$this->descriptor_ = $descriptor;
		$fields = array();
		foreach ( $descriptor->fields as $fieldDescriptor )
			$fields[] = new Field( $fieldDescriptor );
		$this->fields_ = $fields;
	}

	public function __get( $name ) {
		if ( $name === 'fields' )
			return $this->fields_;
		return @$this->descriptor_->{$name};
	}
};