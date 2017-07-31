<?php
namespace LibWeb\util;

abstract class ArrayIteratorBase implements \Iterator, ArrayInterface {
	use ArrayTraits;

	private $inner_;
	private $status_;

	public function __construct( \Iterator $innerIterator ) {
		$this->inner_    = $innerIterator;
		$this->status_   = (object) array(
			"valid"   => false,
			"key"     => null,
			"current" => null,
		);
	}

	// Implements PDO iterator
	public function current() { return $this->status_->current; }
	public function key()     { return $this->status_->key; }
	public function valid()   { return $this->status_->valid; }
	public function rewind() {
		$this->inner_->rewind();
		$this->_iteratorRefreshStatus();
	}
	public function next() {
		$this->inner_->next();
		$this->_iteratorRefreshStatus();
	}

	private function _iteratorRefreshStatus() {
		$this->update( $this->inner_, $this->status_ );
		if ( !$this->status_->valid ) {
			$this->status_->key     = null;
			$this->status_->current = null;
		}
	}

	public function cache() {
		return $this->inner_->cache();
	}
	public function tryCache() {
		if ( !is_callable( array( $this->inner_ ), 'tryCache' ) )
			return false;
		return $this->inner_->tryCache();
	}
	abstract protected function update( $it, $status );
};