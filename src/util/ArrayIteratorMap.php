<?php
namespace LibWeb\util;

class ArrayIteratorMap extends ArrayIteratorBase {
	private $callback_;
	public function __construct( \Iterator $innerIterator, $callback ) {
		parent::__construct( $innerIterator );
		$this->callback_ = $callback;
	}
	protected function update( $it, $status ) {
		$status->valid = $it->valid();
		if ( $status->valid ) {
			$status->key     = $it->key();
			$status->current = call_user_func( $this->callback_, $it->current() );
		}
	}
};