<?php
namespace LibWeb;

/**
 * Debug class using php-debugbar
 */
class Debug {

	private static $debugbar = null;
	private static $renderer = null;
	private static $debugPDO = null;
	
	public static function _setup() {
		if ( self::$debugbar !== null )
			return !!self::$debugbar;
		if ( ( !Config::get( "debug" ) ) || ( php_sapi_name() === 'cli' ) ) {
			self::$debugbar = false;
			return false;
		}
		$debugbar = new \DebugBar\DebugBar;
		$renderer = $debugbar->getJavascriptRenderer();
		
		self::$debugbar = $debugbar;
		self::$renderer = $renderer;

		// Standard collector
		$debugbar->addCollector( new \DebugBar\DataCollector\PhpInfoCollector() );
		$debugbar->addCollector( new \DebugBar\DataCollector\MessagesCollector() );
		$debugbar->addCollector( new \DebugBar\DataCollector\RequestDataCollector() );
		$debugbar->addCollector( new \DebugBar\DataCollector\TimeDataCollector() );
		$debugbar->addCollector( new \DebugBar\DataCollector\MemoryCollector() );
		$debugbar->addCollector( new debug\ExceptionsCollector() );
		
		// Config collector
		$debugbar->addCollector( new \DebugBar\DataCollector\ConfigCollector( Config::raw() ) );

		// PDO collector
		$pdo = DB::instance()->getPDO();
		if ( self::$debugPDO === null ) {
			if ( $pdo instanceof \DebugBar\DataCollector\PDO\TraceablePDO ) {
				self::$debugPDO = $pdo;
				$debugbar->addCollector( new \DebugBar\DataCollector\PDO\PDOCollector( $pdo ) );
			}
		}

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
	public static function disableDebugDB() {
		if ( self::$debugPDO )
			self::$debugPDO->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array( "LibWeb\\debug\\PDOStatementDisableLog", array() ) );
		self::$debugPDO = false;
	}
	/// Collect the debug data
	public static function collect() {
		if ( self::$debugbar )
			self::$debugbar->collect();
	}

	/// Loggers
	public static function exception( $exception ) {
		if ( !self::_setup() )
			return;
		self::$debugbar['exceptions']->addException( $exception );
	}
	public static function info( $message ) {
		if ( !self::_setup() )
			return;
		self::$debugbar['messages']->addMessage( $message );		
	}

	/**
	 * Dump the Javascript for the debug
	 */
	public static function dumpJs( $base ) {
		if ( self::_setup() ) {
			header( "content-type: text/javascript" );
			self::$renderer->setOpenHandlerUrl( $base.'_debug/handler.json' );
			self::$renderer->dumpJsAssets();

			echo "\nPhpDebugBar.$.noConflict(true);\nPhpDebugBar.$( document ).ready(function() {\n\tPhpDebugBar.$(document.body).append(".json_encode( self::$renderer->render() ).");\n});";
		}
		exit;
	}
	/**
	 * Dump the CSS
	 */
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
	/**
	 * Dump the handler for the debug-bar scripts
	 */
	public static function dumpHandler() {
		if ( self::_setup() ) {
			$openHandler = new \DebugBar\OpenHandler( self::$debugbar );
			header( "content-type: text/javascript" );
			echo $openHandler->handle( null, false, false );
		}
		exit;
	}
	/**
	 * Dump every font awesome
	 */
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