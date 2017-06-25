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
	public $fullKey;
	private $errorBag;
	private $parent;
    private $root;

	/// Construct
	public function __construct( $value, $key = null, $parent = null ) {
		$this->initial = $value;
		$this->value   = $value;
		$this->key     = $key;
		if ( $parent ) {
			$this->parent = $parent;
			if ( $parent->fullKey ) {
				$this->fullKey   = $parent->fullKey;
				$this->fullKey[] = $key;
			} else {
				$this->fullKey = array( $key );
			}
			$this->root   = $parent->root;
		} else {
			$this->errorBag = array();
			$this->root     = $this;
		}
	}
	public function getParent() {
		return $this->parent;
	}
	/// Set the errors
	public function setError( $error = true ) {
		$this->error = true;
		$this->root->errorBag[] = (object) array(
			"key" => $this->fullKey,
			"error" => $error
		);
	}
	/// Get the errors
	public function errors() {
		return $this->root->errorBag;
	}
	
};