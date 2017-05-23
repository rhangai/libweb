<?php namespace LibWeb\db;

use \PDO;
use \PDOException;

/**
 * Statement result class
 */
class StatementResult implements \Iterator, \ArrayAccess  {
	
	private $stmt_;
	private $current_;
	private $index_;
	private $cache_;

	// Construct the iterator
	public function __construct( $stmt ) {
		$this->stmt_    = $stmt;
		$this->current_ = null;
		$this->index_   = -1;
		$this->cache_   = null;
	}

	// Implements PDO iterator
	public function current() {
		if ( $this->cache_ )
			return @$this->cache_[ $this->index_ ];
		return $this->current_;
	}
	public function key() { return $this->index_; }
	public function next() {
		if ( !$this->cache_ )
			$this->current_ = $this->stmt_->fetch( PDO::FETCH_OBJ );
		++$this->index_;
	}
	public function rewind() {
		if ( $this->cache_ ) {
			$this->index_ = 0;
			return;
		}
		if ( $this->index_ !== -1 )
			throw new \Exception( "Cannot loop twice over a statement." );
		$this->next();
	}
	public function valid() {
		if ( $this->cache_ )
			return isset($this->cache_[ $this->index_ ]);
		return !!$this->current_;
	}

	// Array access
	public function offsetExists( $offset ) { throw new \LogicException( "Cannot call array methods on a Statement" ); }
	public function offsetGet( $offset )  { throw new \LogicException( "Cannot call array methods on a Statement" ); }
	public function offsetSet( $offset, $value ) { throw new \LogicException( "Cannot call array methods on a Statement" ); }
	public function offsetUnset( $offset ) { throw new \LogicException( "Cannot call array methods on a Statement" ); }

	//
	public function cacheable() {
		if ( $this->cache_ )
			return;
		if ( $this->index_ !== -1 )
			throw new \Exception( "Cannot cache this query after iterating" );
		$this->cache_ = iterator_to_array( $this );
	}

	// Inspect
	public function __debugInfo() {
		$this->cacheable();
		return array(
			"query"   => $this->stmt_->queryString,
			"results" => $this->cache_,
		);
	}
}
