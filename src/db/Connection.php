<?php namespace LibWeb\db;

use \PDO;
use \PDOException;

class Connection {
	private $db;
	private $options;
	private $inTransaction;
	/**
	 * Construct the PDO connection
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->inTransaction = false;
	}
	// Get the internal PDO object
	public function getPDO() { return $this->db; }
	/**
	 * Prepare and execute a query
	 */
	private function prepareExecuteQuery( $query, $data = null ) {
		if ( $data != null ) {
			$stmt = $this->db->prepare( $query );
			$stmt->execute( $data );
			return $stmt;
		} else {
			return $this->db->query( $query );
		}
	}
	/**
	 * Fetch a single object
	 */
	public function fetchOne( $query, $data = null, $options = null ) {
		$stmt = $this->prepareExecuteQuery( $query, $data );
		if ( !$options ) {
			$result = $stmt->fetch( PDO::FETCH_OBJ );
		} else {
			$result = $stmt->fetch( $fetchMode, $fetchArg );
		}
		$stmt->closeCursor();
		return $result;
	}
	/**
	 * Fetch all methods
	 */
	public function fetchAll( $query, $data = null, $options = null ) {
		$stmt   = $this->prepareExecuteQuery( $query, $data );
		return new StatementResult( $stmt );
	}
	/**
	 * Execute a query
	 */
	public function execute( $query, $data = null ) {
		$stmt = $this->prepareExecuteQuery( $query, $data );
		return (object) array(
			"id"    => $this->db->lastInsertId(),
			"count" => $stmt->rowCount()
		);
	}
	/**
	 * Insert a data on a given table
	 */
	public function insertInto( $table, $data ) {
		$db		= $this->db;
		$table	= $this->quoteIdentifier( $table );
		$data	= is_object($data) ? ((array)$data) : $data;
		if ( !is_array($data) )
			throw new \Exception( "Invalid data. Must be array or object." );
		
		$fields = '('.implode( ',', array_map( array( $this, 'quoteIdentifier' ), array_keys( $data ) ) ).')';
		$values = array_values( $data );
		$query = "INSERT INTO ".$table.$fields." VALUES (". implode(',', array_fill(0, count( $values ), '?')).")";

		$stmt = $this->prepareExecuteQuery( $query, $values );
		return $this->db->lastInsertId();
	}
	/**
	 * Update exactly one match
	 */
	public function updateOne( $table, $condition, $data ) {
		$db		= $this->db;
		$table	= $this->quoteIdentifier( $table );

		$condition = is_object($condition) ? ((array)$condition) : $condition;
		if ( !is_array( $condition ) || ( count($condition) <= 0 ) )
			throw new \InvalidArgumentException( "Invalid condition. Must be non empty array or object." );
		
		$data	= is_object($data) ? ((array)$data) : $data;
		if ( !is_array($data) )
			throw new \InvalidArgumentException( "Invalid data. Must be array or object." );


		$fields = array();
		$values = array();
		foreach ( $data as $field => $value ) {
			$fields[] = $this->quoteIdentifier( $field ).' = ?';
			$values[] = $value;
		}
		foreach ( $where as $field => $value ) {
			$where[]  = $this->quoteIdentifier( $field ).' = ?';
			$values[] = $value;
		}
		
		$query = "UPDATE ".$table." SET ".implode( " ", $fields )." WHERE ".implode( " ", $where );
		
		$stmt  = $this->prepareExecuteQuery( $query, $values );
	    $count = $stmt->rowCount();
		if ( $count != 1 )
			throw new \RuntimeException( "Query '".$query."' updated more then a row" );
	}
	/**
	 * Create a transaction
	 */
	public function transaction( $cb ) {
		$db = $this->db;
		if ( $this->inTransaction )
			return call_user_func( $cb, $db );
		
		$db->beginTransaction();
		$this->inTransaction = true;
		try {
			$ret = call_user_func( $cb, $db );
			$this->inTransaction = false;
			$db->commit();
		} catch( \Exception $e ) {
			$this->inTransaction = false;
			$db->rollback();
			throw $e;
		}
		return $ret;
	}
	
	// Quote identifier
	public function quoteIdentifier( $identifier ) {
		return "`".str_replace( "`", "``", $identifier )."`";
	}
}