<?php
namespace LibWeb\util;

class ArrayIteratorMap extends ArrayIteratorBase {
	private $callback_;
	public function __construct( \Iterator $innerIterator, $callback ) {
		parent::__construct( $innerIterator );
		if ( is_string( $callback ) )
			$this->callback_ = function( $value ) use ( $callback ) { return $value->{$callback}; };
		else if ( is_callable( $callback ) )
			$this->callback_ = $callback;
		else if ( is_array( $callback ) )
			$this->callback_ = function( $item ) use( $callback ) {
				$ret = array();
				foreach ( $callback as $newkey => $oldkey ) {
					$ret[$newkey] = $item->{$oldkey};
				}
				return (object) $ret;
			};
		else
			throw new \InvalidArgumentException( "Callback for map must be a string or a callable" );
	}
	protected function update( $it, $status ) {
		$status->valid = $it->valid();
		if ( $status->valid ) {
			$status->key     = $it->key();
			$status->current = call_user_func( $this->callback_, $it->current() );
		}
	}
};