<?php namespace LibWeb\db;

use \PDO;
use \PDOException;

class Connection {
	private $db;
	private $options;
	/**
	 * Construct the PDO connection
	 */
	public function __construct( $db ) {
		$this->db = $db;
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
	 * Create a transaction
	 */
	public function transaction( $cb ) {
		$db = $this->db;
		$db->beginTransaction();
		try {
			$ret = call_user_func( $cb, $db );
			$db->commit();
		} catch( \Exception $e ) {
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