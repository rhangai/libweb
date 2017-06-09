<?php
namespace LibWeb\validator;

/**
 *
 */
class State {

	public $initial;
	public $value;
	public $key;
	private $parent;
    private $root;

	public function __construct( $value, $key = null, $parent = null ) {
		$this->initial = $value;
		$this->value   = $value;
		$this->key     = $key;
		if ( $parent ) {
			$this->parent = $parent;
			$this->root   = $parent->root;
		} else {
			$this->root = $this;
		}
	}

	public function setError() {
		
	}
	
};