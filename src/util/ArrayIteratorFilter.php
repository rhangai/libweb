<?php
namespace LibWeb\util;

class ArrayIteratorFilter extends ArrayIteratorBase {
	private $callback_;
	public function __construct( \Iterator $innerIterator, $callback ) {
		parent::__construct( $innerIterator );
		if ( is_string( $callback ) ) {
			if ( $callback[ 0 ] === '!' )
				$this->callback_ = function( $value ) use ( $callback ) { return !!$value->{$callback}; };
		} else if ( is_callable( $callback ) )
			$this->callback_ = $callback;
		else
			throw new \InvalidArgumentException( "Callback for map must be a string or a callable" );
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