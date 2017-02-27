<?php namespace LibWeb;

class Config {

	private static $config = array();

	public static function feedJSON( $file ) {
		self::$config = self::mergeConfig( self::$config, json_decode( $file, true ) );
	}
	public static function feed( $obj ) {
		self::$config = self::mergeConfig( self::$config, $obj );
	}

	public static function get( $name ) {
		$name  = explode( '.', $name );
		$value = self::$config;
		for ( $i = 0, $len = count( $name ); $i < $len; ++$i ) {
			$value = $value[ $name[ $i ] ];
		}
		return $value;
	}

	public static function mergeConfig( $config1, $config2 )
	{
		$config = $config1;
		foreach ( $config2 as $key => $value )
		{
			$overwrite = false;
			$newkey    = $key;
			if ( $key[0] === '!' ) {
				$overwrite = true;
				$newkey = substr( $key, 1 );
			}
			if ( !$overwrite && is_array( $value ) && isset($config[$key]) && is_array ($config[$key]) ) {
				$config[$newkey] = self::mergeConfig( $config[$key], $value );
			} else {
				$config[$newkey] = $value;
			}
		}
		return $config;
	}
	
}
