<?php namespace LibWeb;

use \PDO;
use \PDOException;

class DB {
	
	private $db;
	private $options;

	public function __construct( $options = array() ) {
		$this->db = $this->createConnection( $options );
	}
	
	protected function createConnection( $options ) {
		return new PDO(
		    @$options['url'] ?: Config::get( 'PDO.url' ),
		    @$options['user'] ?: Config::get( 'PDO.user' ),
			@$options['password'] ?: Config::get( 'PDO.password' ),
			array( PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING )
		);
	}

	public function fetchOne( $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		if ( $data != null ) {
			$stmt = $this->db->prepare( $query );
		    $stmt->execute( $data );
			$result = $stmt->fetch( $fetchMode, $fetchArg );
			$stmt->closeCursor();
			return $result;
		} else {
			$stmt   = $this->db->query( $query );
			$result = $stmt->fetch( $fetchMode, $fetchArg );
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
		    return $stmt->fetchAll( $fetchMode, $fetchArg );
		} else {
			$stmt = $db->query( $query );
			return $stmt->fetchAll( $fetchMode, $fetchArg );
		}
	}
	public function fetchAllSafe( &$err, $query, $data = null, $fetchMode = PDO::FETCH_OBJ, $fetchArg = null ) {
		return $this->callSafe( $err, 'fetchAll', array( $query, $data, $fetchMode, $fetchArg ) );
	}

	public function execute( $query, $data = null ) {
		$db   = $this->db;
		if ( $data != null ) {
		    $db->prepare( $query );
		    $stmt->execute( $data );
		} else {
		    $db->execute( $query );
		}
		return $db->lastInsertId();
	}
	public function executeSafe( &$err, $query, $data = null ) {
		return $this->callSafe( $err, 'execute', array( $query, $data ) );
	}

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
			return call_user_func_array( array( $this, $methods ), $args );
		} catch ( PDOException $e ) {
			$err = $e;
		}
		return null;
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
