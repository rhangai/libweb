<?php namespace LibWeb;

use \PDO;
use \PDOException;

class DB {
	
	private $db;
	private $options;

	public function __construct( $options = array() ) {
		$this->db = $this->createConnection( $options );
	}

	public function getPDO() { return $this->db; }
	
	protected function createConnection( $options ) {
		return new PDO(
		    @$options['url'] ?: Config::get( 'PDO.url' ),
		    @$options['user'] ?: Config::get( 'PDO.user' ),
			@$options['password'] ?: Config::get( 'PDO.password' ),
			array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
		);
	}

	public function fetchOne( $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		if ( $data != null ) {
			$stmt = $this->db->prepare( $query );
		    $stmt->execute( $data );
			$result = $fetchArg != null ? $stmt->fetch( $fetchMode, $fetchArg ) : $stmt->fetch( $fetchMode );
			$stmt->closeCursor();
			return $result;
		} else {
			$stmt   = $this->db->query( $query );
			$result = $fetchArg != null ? $stmt->fetch( $fetchMode, $fetchArg ) : $stmt->fetch( $fetchMode );
			$stmt->closeCursor();
			return $result;
		}
	}
	public function fetchOneSafe( &$err, $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		return $this->callSafe( $err, 'fetchOne', array( $query, $data, $fetchMode, $fetchArg ) );
	}

	public function fetchAll( $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		$db   = $this->db;
		if ( $data != null ) {
			$stmt   = $db->prepare( $query );
		    $stmt->execute( $data );
			return ( $fetchArg != null ) ? $stmt->fetchAll( $fetchMode, $fetchArg ) : $stmt->fetchAll( $fetchMode );
		} else {
			$stmt = $db->query( $query );
			return ( $fetchArg != null ) ? $stmt->fetchAll( $fetchMode, $fetchArg ) : $stmt->fetchAll( $fetchMode );
		}
	}
	public function fetchAllSafe( &$err, $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		return $this->callSafe( $err, 'fetchAll', array( $query, $data, $fetchMode, $fetchArg ) );
	}
	
	/**
	 * Execute a query
	 */
	public function execute( $query, $data = null ) {
		$db   = $this->db;
		if ( $data != null ) {
		    $stmt = $db->prepare( $query );
		    $stmt->execute( $data );
		} else {
		    $db->query( $query );
		}
		return $db->lastInsertId();
	}
	public function executeSafe( &$err, $query, $data = null ) {
		return $this->callSafe( $err, 'execute', array( $query, $data ) );
	}
	
	/**
	 * Insert a data on the table
	 */
	public function insertInto( $table, $data ) {
		$db     = $this->db;
		$table  = $this->quoteIdentifier( $table );
		$fields = '('.implode( ',', array_map( array( $this, 'quoteIdentifier' ), array_keys( $data ) ) ).')';
		$values = array_values( $data );
	    $query = "INSERT INTO ".$table.$fields." VALUES (". implode(',', array_fill(0, count( $values ), '?')).")";
		return $this->execute( $query, $values );
	}
	public function insertIntoSafe( &$err, $table, $data ) {
		return $this->callSafe( $err, 'insertInto', array( $table, $data ) );
	}

	/**
	 * Execute a query multiple times
	 */
	public function executeArray( $query, $data, $map = null, $cb = null ) {
		$db     = $this->db;
		$stmt   = $db->prepare( $query );
		
		foreach( $data as $item ) {
			if ( $map )
				$item = call_user_func( $map, $item );
		    $stmt->execute( $item );
			if ( $cb )
				call_user_func( $cb, $db->lastInsertId() );
		}
	}
	public function executeArraySafe( &$err, $query, $data, $map = null, $cb = null ) {
		return $this->callSafe( $err, 'executeArray', array( $query, $data, $map, $cb ) );
	}


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
    public function transactionSafe( &$err, $cb ) {
		return $this->callSafe( $err, 'transaction', array( $cb ) );
	}
	
	public function writeFileStream( $stream, $nome = "", $tipo = "", $info = "" ) {
		$db   = $this->db;
		$stmt = $db->prepare( "INSERT INTO File (Nome,Tipo,Info,Criado,Dados) VALUES (?,?,?,NOW(),?)" );

		$stmt->bindParam( 1, $nome );
		$stmt->bindParam( 2, $tipo );
		$stmt->bindParam( 3, $info );
		$stmt->bindParam( 4, $stream, PDO::PARAM_LOB );
		$stmt->execute();
		return $db->lastInsertId();
	}

	public function readFileStream( $id, $cb ) {
		$db   = DB::get();
		$stmt = $db->prepare( "SELECT Nome,Tipo,Info,Dados FROM File WHERE ID=?" );

		$stmt->execute( array($id) );

		$stmt->bindColumn( 1, $nome,   PDO::PARAM_STR );
		$stmt->bindColumn( 2, $tipo,   PDO::PARAM_STR );
		$stmt->bindColumn( 3, $info,   PDO::PARAM_STR );
		$stmt->bindColumn( 4, $stream, PDO::PARAM_LOB );
		$stmt->fetch( PDO::FETCH_BOUND );
		if ( $cb ) {
			if ( is_string( $stream ) ) {
				$string = $stream;
				$stream = fopen('php://memory','r+');
				fwrite( $stream, $string );
				rewind( $stream );
			}
			return call_user_func( $cb, $stream, $nome, $tipo, $info );
		}
		return $stream;
	}
	
	// Call safe
    private function callSafe( &$err, $method, $args ) {
		try {
			return call_user_func_array( array( $this, $method ), $args );
		} catch ( PDOException $e ) {
			$err = $e;
		}
		return null;
	}

	// Quote identifier
    public function quoteIdentifier( $identifier ) {
		return "`".str_replace( "`", "``", $identifier )."`";
	}

	// Instance
	private static $instances = array();
	public static function instance() {
		$k = get_called_class();
		if ( !@self::$instances[ $k ] )
			self::$instances[ $k ]  = new static;
	    return self::$instances[ $k ];
	}
}
