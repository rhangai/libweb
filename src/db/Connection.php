<?php namespace LibWeb\db;

use \PDO;
use \PDOException;

class Connection {
	private $db;
	private $options;
	private $inTransaction;
	private $lastQuery;
	private $debugMode;
	/**
	 * Construct the PDO connection
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->inTransaction = false;
		$this->lastQuery     = null;
	}
	// Get the internal PDO object
	public function getPDO() { return $this->db; }
	/// Enables debug mode on this connection
	public function enableDebug() {
		if ( !$this->debugMode ) {
			$this->debugMode = true;
			if ( !$this->inTransaction )
				$this->db->beginTransaction();
		}
	}
	/**
	 * Prepare and execute a query
	 */
	private function prepareExecuteQuery( $query, $data = null ) {
		$this->lastQuery = $query;
		if ( $data != null ) {
			$stmt = $this->db->prepare( $query );
			$stmt->execute( $data );
			return $stmt;
		} else {
			return $this->db->query( $query );
		}
	}
	/**
	 * Ensure a single object is fetched.
	 */
	public function ensureOne( $query, $data = null ) {
		$stmt = $this->prepareExecuteQuery( $query, $data );
		$result = $stmt->fetch( PDO::FETCH_OBJ );
		$stmt->closeCursor();
		if ( !$result )
			throw new \RuntimeException( "Query did not return anything. '".$query."' with data ".print_r( $data, true ) );
		return $result;
	}
	/**
	 * Fetch a single object
	 */
	public function fetchOne( $query, $data = null ) {
		$stmt = $this->prepareExecuteQuery( $query, $data );
		$result = $stmt->fetch( PDO::FETCH_OBJ );
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
		
		$stmt = $this->db->prepare( $query );

		$i = 1;
		foreach( $values as $value ) {
			$stmt->bindValue( $i, $value, static::getPDOType( $value ) );
			++$i;
		}
		$stmt->execute();
		//$stmt = $this->prepareExecuteQuery( $query, $values );
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
			throw new \RuntimeException( "Query '".$this->lastQuery."' updated more then a row" );
	}
	/**
	 * Create a transaction
	 */
	public function transaction( $cb ) {
		$db = $this->db;
		if ( $this->inTransaction )
			return call_user_func( $cb, $db );

		if ( !$this->debugMode )
			$db->beginTransaction();
		$this->inTransaction = true;
		try {
			$ret = call_user_func( $cb, $db );
			$this->inTransaction = false;
			if ( !$this->debugMode )
				$db->commit();
		} catch( \Exception $e ) {
			$this->inTransaction = false;
		    if ( !$this->debugMode )
				$db->rollback();
			throw $e;
		}
		return $ret;
	}
	
	// Quote identifier
	public function quoteIdentifier( $identifier ) {
		return "`".str_replace( "`", "``", $identifier )."`";
	}
	// Get PDO insert type
	public static function getPDOType( $value ) {
		if ( is_resource($value) )
			return PDO::PARAM_LOB;
		return PDO::PARAM_STR;
	}
}