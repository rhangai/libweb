<?php
namespace LibWeb\validator;

/**
 * Estado do validador
 */
class State {

	public $initial;
	public $value;
	public $key;
	public $error;
	private $errorBag;
	private $parent;
    private $root;

	/// Construct
	public function __construct( $value, $key = null, $parent = null ) {
		$this->initial = $value;
		$this->value   = $value;
		if ( $parent ) {
			$this->parent = $parent;
			if ( $parent->key ) {
				$this->key = $parent->key;
				$this->key[] = $key;
			} else {
				$this->key = array( $key );
			}
			$this->root   = $parent->root;
		} else {
			$this->errorBag = array();
			$this->root     = $this;
		}
	}
	/// Set the errors
	public function setError( $error = true ) {
		$this->error = true;
		$this->root->errorBag[] = (object) array(
			"key" => $this->key,
			"error" => $error
		);
	}
	/// Get the errors
	public function errors() {
		return $this->root->errorBag;
	}
	
};