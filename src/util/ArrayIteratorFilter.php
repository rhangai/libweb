<?php
namespace LibWeb\util;

class ArrayIteratorFilter extends ArrayIteratorBase {
	private $callback_;
	public function __construct( \Iterator $innerIterator, $callback ) {
		parent::__construct( $innerIterator );
		$this->callback_ = $callback;
	}
	protected function update( $it, $status ) {
		while ( $it->valid() ) {
			$status->key     = $it->key();
			$status->current = $it->current();

			$test = call_user_func( $this->callback_, $status->current, $status->key );
			if ( $test )
				return;
			
			$it->next();
		}
		$status->valid = false;
	}
};