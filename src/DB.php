<?php namespace LibWeb;

use \PDO;
use \PDOException;
use LibWeb\db\Connection;

class DB {

	/// Protected connection
	protected static function createConnection( $options = array() ) {
		$options = (array) $options;

		// Merge PDO options
		$pdoOptions = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_FOUND_ROWS => true,
		);
		if ( is_array( @$options['options'] ) )
			$pdoOptions += $options['options'];
		
		$pdo = new PDO(
			@$options['url'] ?: Config::get( 'PDO.url' ),
			@$options['user'] ?: Config::get( 'PDO.user' ),
			@$options['password'] ?: Config::get( 'PDO.password' ),
			$pdoOptions
		);

		$pdo = Debug::wrapPDO( $pdo );
		
		return $pdo;
	}

	public static function enableDebug() {
		$db = static::instance();
		return $db->enableDebug();
	}
	public static function ping() {
		$db = static::instance();
		return $db->ping();
	}
	public static function ensureOne( $query, $data = null ) {
		$db = static::instance();
		return $db->ensureOne( $query, $data );
	}
	public static function fetchOne( $query, $data = null ) {
		$db = static::instance();
		return $db->fetchOne( $query, $data );
	}
	public static function fetchAll( $query, $data = null ) {
		$db = static::instance();
		return $db->fetchAll( $query, $data );
	}
	public static function execute( $query, $data = null ) {
		$db = static::instance();
		return $db->execute( $query, $data );
	}
	public static function executeOne( $query, $data = null ) {
		$db = static::instance();
		return $db->executeOne( $query, $data );
	}
	public static function insertInto( $table, $data ) {
		$db = static::instance();
		return $db->insertInto( $table, $data );
	}
	public static function updateOne( $table, $condition, $data ) {
		$db = static::instance();
		return $db->updateOne( $table, $condition, $data );
	}
	public static function transaction( $cb ) {
		$db = static::instance();
		return $db->transaction( $cb );
	}
	public static function quoteIdentifier( $what ) {
		$db = static::instance();
		return $db->quoteIdentifier( $what );
	}
	public static function isSingleton() {
		return false;
	}

	
	// Instance
	private static $instances = array();
	public static function instance() {
		Debug::_setup();
		
		$k = '_';
		if ( static::isSingleton() )
			$k = get_called_class();
		if ( !@DB::$instances[ $k ] )
			@DB::$instances[ $k ]	= new Connection( function() { return static::createConnection(); } );
		return DB::$instances[ $k ];
	}
}