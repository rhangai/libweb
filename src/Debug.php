<?php
namespace LibWeb;

/**
 * Debug class using php-debugbar
 */
class Debug {

	private static $debugbar = null;
	private static $renderer = null;
	
    public static function _setup() {
		if ( self::$debugbar !== null )
			return !!self::$debugbar;
		if ( !Config::get( "debug" ) ) {
			self::$debugbar = false;
			return false;
		}
		$debugbar = new \DebugBar\StandardDebugBar;
		$renderer = $debugbar->getJavascriptRenderer();
		
		self::$debugbar = $debugbar;
		self::$renderer = $renderer;

		$pdo = DB::instance()->getPDO();
		if ( $pdo instanceof \DebugBar\DataCollector\PDO\TraceablePDO )
			$debugbar->addCollector( new \DebugBar\DataCollector\PDO\PDOCollector( $pdo ) );
		$debugbar->addCollector( new \DebugBar\DataCollector\ConfigCollector( Config::raw() ) );

		// Set storage
		$storage = new \DebugBar\Storage\FileStorage( sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php-debugbar" );
		$debugbar->setStorage( $storage );
		$debugbar->sendDataInHeaders( true );
		
		return true;
	}
	/**
	 * Wrap a PDO to the debugbar
	 */
	public static function wrapPDO( $pdo ) {
		if ( !self::_setup() )
			return $pdo;
		return new \DebugBar\DataCollector\PDO\TraceablePDO( $pdo );
	}

	public static function collect() {
		if ( self::$debugbar !== null )
			self::$debugbar->collect();
	}
	public static function collectException( $exception ) {
		if ( !self::_setup() )
			return;
		self::$debugbar['exceptions']->addException( $exception );
	}


	public static function dumpJs( $base ) {
		if ( self::_setup() ) {
		    header( "content-type: text/javascript" );
			self::$renderer->setOpenHandlerUrl( $base.'_debug.handler' );
			self::$renderer->dumpJsAssets();

			echo "PhpDebugBar.$( document ).ready(function() { PhpDebugBar.$(document.body).append(".json_encode( self::$renderer->render() )."); });";
		}
		exit;
	}
	
	public static function dumpCss() {
		if ( self::_setup() ) {
		    header( "content-type: text/css" );
			ob_start();
			self::$renderer->dumpCssAssets();
			$content = ob_get_clean();
			$content = str_replace( "../fonts/fontawesome-webfont", "./_debug/fontawesome-webfont", $content );
			echo $content;
		}
		exit;
	}
	
	public static function dumpHandler() {
		if ( self::_setup() ) {
			$openHandler = new \DebugBar\OpenHandler( self::$debugbar );
			header( "content-type: text/javascript" );
			echo $openHandler->handle( null, false, false );
		}
		exit;
	}
	
	public static function dumpFontAwesome( $uri ) {
		if ( self::_setup() ) {
			$base = self::$renderer->getBasePath().'/vendor/font-awesome/fonts/';
			$path = $base . substr( $uri, 8 );
			$realpath = realpath( $path );
			if ( substr( $realpath, 0, strlen( $base ) ) !== $base )
				exit;
			header( "content-type: ".mime_content_type( $realpath ) );
		    readfile( $realpath );
		}
		exit;
	}
	
};