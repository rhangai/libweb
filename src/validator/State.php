<?php
namespace LibWeb\validator;

/**
 * Estado do validador
 */
class State {

	public $value;

	private $done_;
	private $initial_;
	private $errorBag_;
	private $parent_;
    private $root_;
	private $key_;

	/// Construct
	public function __construct( $value, $key = null, $parent = null ) {
		$key = ($key !== null) ? ((array) $key) : array();
		
		$this->value     = $value;

		$this->done_     = false;
		$this->initial_  = $value;
		$this->errorBag_ = array();
		$this->parent_   = $parent;
		if ( $parent ) {
			$this->root_ = $parent->root_;
			$this->key_  = $parent->key_ ? array_merge( $parent->key_, $key ) : $key;
		} else {
			$this->root_ = $this;
			$this->key_  = $key;
		}
	}
	public function getParent() {
		return $this->parent_;
	}
	public function getKey() {
		return $this->key_;
	}
	/// Set the errors
	public function setError( $error = true ) {
		if ( $error instanceof RuleException )
			$error = $error->getMessage();
		$this->errorBag_[] = (object) array(
			"key"   => $this->key_,
			"error" => $error
		);
	}
	/// Merge the errors
	public function mergeErrors( $errors ) {
		$this->errorBag_ = array_merge( $this->errorBag_, $errors );
	}
	/// Get the errors
	public function errors() {
		return $this->errorBag_;
	}
	/// Check if done
	public function isDone() {
		return $this->done_;
	}
	/// Mark as done
	public function markDone() {
		$this->done_ = true;
	}
	
};