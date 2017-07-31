<?php namespace LibWeb;

use \PDO;
use \PDOException;
use LibWeb\db\Connection;

class DB {

	/// Protected connection
	protected static function createConnection( $options = array() ) {
		return new PDO(
			@$options['url'] ?: Config::get( 'PDO.url' ),
			@$options['user'] ?: Config::get( 'PDO.user' ),
			@$options['password'] ?: Config::get( 'PDO.password' ),
			array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
		);
	}

	public static function fetchOne( $query, $data = null, $options = null ) {
		$db = static::instance();
		return $db->fetchOne( $query, $data, $options );
	}
	public static function fetchAll( $query, $data = null, $options = null ) {
		$db = static::instance();
		return $db->fetchAll( $query, $data, $options );
	}
	public static function execute( $query, $data = null ) {
		$db = static::instance();
		return $db->execute( $query, $data );
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
	
	// Instance
	private static $instances = array();
	public static function instance() {
		$k = get_called_class();
		if ( !@self::$instances[ $k ] )
			self::$instances[ $k ]	= new Connection( static::createConnection() );
		return self::$instances[ $k ];
	}
}