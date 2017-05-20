<?php
namespace LibWeb;

use LibWeb\api\Response;
use LibWeb\api\Request;
use LibWeb\api\ExceptionNotFound;

class API {
    private $rootNamespace;
    private $rootDir;
	/// Construct the API
	public function __construct( $namespace = null, $dir = null ) {
		$this->rootNamespace = $namespace;
		$this->rootDir       = $dir;
	}
	/// Dispatch the URI
	public function dispatch( $uri = null, $method = null ) {
		$req = Request::createFromGlobals( $uri, $method );
		return $this->dispatchRequest( $req );
	}
	/// Dispatch the requesti
	public function dispatchRequest( $req, $send = true ) {
		$res   = new Response;
		$found = $this->dispatchInternal( $req, $res );
		if ( $found === false ) {
			$ret = $this->handleNotFound( $req, $res );
			if ( $ret != null )
				$res->data( $ret );
		}	
		if ( $send )
			$this->sendResponse( $req, $res );
		return $res;
	}
	/**
	 * Internally dispatches the API
	 */
	private function dispatchInternal( $req, $res ) {
		$paths = array_values( array_filter( explode( "/", $req->uri() ) ) );
		$len   = count( $paths );
		
		$preHandlers = array();

		
		$path    = array_slice( $paths, 0, $len - 1 );
	    $obj     = $this->resolvePath( $path, $this->rootDir, $this->rootNamespace );
		if ( !$obj )
			return false;
		$functionName = $this->resolveFunction( $obj, $paths[ $len - 1 ], $req );
	    
		$method      = $req->method();
		$mainHandler = array( $obj, strtoupper($method).'_' . $functionName );
		if ( !$mainHandler || !is_callable( $mainHandler ) )
			return false;
		
		// Check for middleware on the current path
		$handler  = array( $this, "middleware" );
		if ( is_callable( $handler ) )
			$preHandlers[] = $handler;
		
		// Check for middlewares on the path
		for ( $i = 0; $i < $len - 1; ++$i ) {
			$path   = array_slice( $paths, 0, $i );
			$path[] = "_Parent";
			$obj    = $this->resolvePath( $path, $this->rootDir, $this->rootNamespace );
			if ( $obj ) {
			    $handler = array( $obj, "middleware" );
				if ( is_callable( $handler ) )
					$preHandlers[] = $handler;
			}
		}

		// Call handlers
		$handlers   = $preHandlers;
		$handlers[] = $mainHandler;
		foreach( $handlers as $handler ) {
			try {
				$ret = call_user_func( $handler, $req, $res );
			} catch( \Exception $e ) {
			    $ret = $this->handleException( $e, $req, $res );
			}
			if ( $ret != null ) {
				$res->data( $ret );
				break;
			} else if ( $res->getData() )
				break;
		}
		return true;
	}
	/// Resolve a path to a object
	protected function resolvePath( $path, $rootDir, $rootNamespace ) {
		if ( !$path )
			return $this;
	    $path = array_map(function( $item ) { return API::_toPascalCase( $item, true ); }, $path );
		$file = implode( "/", $path ).".php";
		if ( $rootDir )
			$file = $rootDir."/".$file;
		$included = @include $file;
		if ( $included === false )
			 return null;

		$len = count( $path );
		if ( !$rootNamespace ) {
			$klassname = "\\".$path[ $len - 1 ]."API";
		} else {
			$klassname = $rootNamespace."\\".implode( "\\", $path )."API";
		}
		$obj = new $klassname;
		return $obj;
	}
	/// Resolve a function name
	protected function resolveFunction( $obj, $name, $req ) {
		return self::_toPascalCase( $name );
	}
	/// Convert to pascal case
	private static function _toPascalCase( $str, $capitalizeFirst = false ) {
		$str = str_replace(' ', '', ucwords(str_replace('-', ' ', $str)));
		if ( !$capitalizeFirst )
			$str = lcfirst( $str );
		return $str;
	}
	/// Format the response
	public function formatResponse( $status, $data, $req, $res ) {
		if ( is_object( $data) && is_callable( array( $data, "serializeAPI" ) ) )
			$data = $data->serializeAPI();
		return $data;
	}
	/// Defaults to sending JSON api
	public function sendResponse( $req, $res ) {
		$responseCode = $res->getCode() ?: 200;
		$headers      = $res->getHeaders();
		$data         = $res->getData();
		$raw          = $res->getRaw();
		
		// Status of the response
		$status = $responseCode === 200 ? "success" : "error";

		// Send headers
		http_response_code( $responseCode );
		if ( !$raw && !isset( $headers["content-type"] ) )
			header( "content-type: application/json" );
		foreach ( $headers as $key => $value ) {
			header( $key.": ".$value );
		}

		// Send data
		if ( $data instanceof \Closure )
			call_user_func( $data );
		else if ( $raw )
			echo $data;
		else
			echo json_encode( $this->formatResponse( $status, $data, $req, $res ) );
	}
	/// Internal not found handler (May be overwritten)
	public function handleNotFound( $req, $res ) {
		$res->code( 404 );
		$res->header( "content-type", "text/text" );
		$res->raw( "Cannot ".$req->method()." ".$req->uri() );
	}
	/// Exception handler (May be overwritten)
	public function handleException( $e, $req, $res ) {
		$res->code( 500 );
		$res->data( $e );
	}
	
};
