<?php namespace LibWeb;

use \PDO;

class DBException extends \Exception {

	public function __construct( $code, $info ) {
		parent::__construct( $info );
	}
};
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

	public function fetchOne( $query, $data = null, $fetchMode = PDO::FETCH_OBJ ) {
		if ( $data != null ) {
			$stmt = $this->db->prepare( $query );
		    $stmt->execute( $data );
			$result = $stmt->fetch( $fetchMode );
			$stmt->closeCursor();
			return $result;
		} else {
			$stmt   = $this->db->query( $query );
			$result = $stmt->fetch( $fetchMode );
			$stmt->closeCursor();
			return $result;
		}
	}

	public function fetchAll( $query, $data = null, $fetchMode = PDO::FETCH_OBJ ) {
		$db   = $this->db;
		if ( $data != null ) {
			$stmt   = $db->prepare( $query );
		    $stmt->execute( $data );
		    return $stmt->fetchAll( $fetchMode );
		} else {
			$stmt = $db->query( $query );
			return $stmt->fetchAll( $fetchMode );
		}
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

	public static function throwErrorFrom( $obj ) {
		throw new DBException( json_encode( array( "info" => $obj->errorInfo(), "code" => $obj->errorCode() ) ) );
	}

	// Make safe
    private function makeSafe( &$err, $method, $args ) {
		try {
			return call_user_func_array( array( $this, $methods ), $args );
		} catch ( DBException $e ) {
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
