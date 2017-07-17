<?php namespace LibWeb\db;

use \PDO;
use \PDOException;
use LibWeb\util\ArrayInterface;

/**
 * Statement result class
 */
class StatementResult implements \Iterator, ArrayInterface {
	use \LibWeb\util\ArrayTraits;
	
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
	// Try to make the function as cache
	public function cache() {
		if ( !$this->tryCache() )
			throw new \Exception( "Cannot cache this query after iterating" );
		return $this->cache_;
	}
	// Cache
	public function tryCache() {
		if ( $this->cache_ )
			return true;
		if ( $this->index_ !== -1 )
			return false;
		$this->cache_ = iterator_to_array( $this );
		return true;
	}
	// Inspect
	public function __debugInfo() {
		$this->tryCache();
		return array(
			"query"   => $this->stmt_->queryString,
			"results" => $this->cache_ === null ? "[Not cached]" : $this->cache_,
		);
	}
}
