<?php
namespace LibWeb;

use LibWeb\api\Response;
use LibWeb\api\Request;
use LibWeb\api\ExceptionNotFound;

class API {
    private $rootNamespace;
    private $rootDir;
	private $ignoreFiles;
	/// Construct the API
	public function __construct( $namespace = null, $dir = null ) {
		$this->rootNamespace = $namespace;
		$this->rootDir       = $dir;
	}
	/// Add a few ignore files
	public function addIgnore( $ignoreFiles ) {
		if ( !$ignoreFiles )
			return;
		if ( is_array( $ignoreFiles ) ) {
			foreach ( $ignoreFiles as $file )
				$this->addIgnore( $file );
			return;
		}

		$this->ignoreFiles[] = realpath( $ignoreFiles );
	}
	/// Dispatch the URI
	public function dispatch( $base = null, $uri = null, $method = null ) {
		$req = Request::createFromGlobals( $base, $uri, $method );
		return $this->dispatchRequest( $req );
	}
	/// Dispatch the requesti
	public function dispatchRequest( $req, $send = true ) {
		$res    = new Response;
		$method = $this->dispatchInternal( $req, $res );
		if ( $method === false ) {
			$ret = $this->handleNotFound( $req, $res );
			if ( $ret != null )
				$res->data( $ret );
		}
		$headersOnly = ($method === 'OPTIONS');
		if ( $send )
			$this->sendResponse( $req, $res, $headersOnly );
		return $res;
	}
	/**
	 * Internally dispatches the API
	 */
	private function dispatchInternal( $req, $res ) {
		$uri   = $req->uri();
		$base  = $req->base();
		if ( $base ) {
			$len = strlen( $base );
			if ( substr( $uri, 0, $len ) !== $base )
				return false;
			$uri = '/'.substr( $uri, $len );
		}
		
		$paths = array_values( array_filter( explode( "/", $uri ) ) );
		$len   = count( $paths );
		
		$preHandlers = array();


		
		$path    = array_slice( $paths, 0, $len - 1 );
	    $obj     = $this->resolvePath( $path, $this->rootDir, $this->rootNamespace );
		if ( !$obj )
			return false;
		$functionName = $this->resolveFunction( $obj, $paths[ $len - 1 ], $req );
	    
		$method = strtoupper( $req->method() );
		if ( $method === 'OPTIONS' ) {
			$this->handleOptions( $req, $res );
			return $method;
		}
		$mainHandler = array( $obj, $method.'_' . $functionName );
		if ( !$mainHandler || !is_callable( $mainHandler ) )
			return false;

		// Handle options if found
		$this->handleOptions( $req, $res );
		
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
		return $method;
	}
	/// Resolve a path to a object
	protected function resolvePath( $path, $rootDir, $rootNamespace ) {
		if ( !$path )
			return $this;
	    $path[ count($path) - 1 ] = API::_toPascalCase( $path[ count($path) - 1 ], true );
		$file = implode( "/", $path ).".php";
		if ( $rootDir )
			$file = $rootDir."/".$file;
		$file = realpath( $file );
		if ( in_array( $file, $this->ignoreFiles ) )
			return null;
		
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
	public function formatResponse( $status, $data, $errorType, $req, $res ) {
		if ( $data instanceof \Exception ) {
			if ( $data && is_callable( array( $data, "serializeAPI" ) ) )
				return $data->serializeAPI();
			return null;
		}
		
		if ( is_object( $data) && is_callable( array( $data, "serializeAPI" ) ) )
			$data = $data->serializeAPI();
		return $data;
	}
	/// Defaults to sending JSON api
	public function sendResponse( $req, $res, $headersOnly = false ) {
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
		if ( $headersOnly )
			return;

		// Send data
		if ( $data instanceof \Closure )
			call_user_func( $data );
		else if ( $raw )
			echo $data;
		else {
			$errorType = null;
			if ( $data instanceof APIException )
				$errorType = $data->getType();
			$obj = $this->formatResponse( $status, $data, $errorType, $req, $res );
			$this->writeResponse( $obj );
		}
	}
	/// Write the response
	public function writeResponse( $obj ) {
		$this->writeJSON( $obj );
	}
	/// Write a JSON
	public function writeJSON( $obj ) {
		$isArray  = false;
		$isObject = false;
		if ( is_array( $obj ) ) {
			reset( $obj );
			$firstKey = key( $obj );
			end( $obj );
			$lastKey  = key( $obj );
			$size     = count( $obj );
			if ( ( $firstKey === 0 ) && ( $lastKey === ( $size-1 ) ) )
				$isArray = true;
			else
				$isObject = true;
		} else if ( is_object( $obj ) ) {
			if ( $obj instanceof \ArrayAccess )
				$isArray  = true;
			else
				$isObject = true;
		}
		
		if ( $isArray ) {
			echo "[";
			$first = true;
			foreach( $obj as $val ) {
				if ( $first ) {
					$first = false;
				} else {
					echo ",";
				}
				$this->writeJSON( $val );
			}
			echo "]";
		} else if ( $isObject ) {
			echo "{";
			$first = true;
			foreach( $obj as $key => $val ) {
				if ( $first ) {
					$first = false;
				} else {
					echo ",";
				}
				echo '"', $key,'":';
				$this->writeJSON( $val );
			}
			echo "}";
		} else {
			echo json_encode( $obj );
		}
	}
	/// Internal not found handler (May be overwritten)
	public function handleNotFound( $req, $res ) {
		$res->code( 404 );
		$res->header( "content-type", "text/text" );
		$res->raw( "Cannot ".$req->method()." ".$req->uri() );
	}
	/// Options Handler
	public function handleOptions( $req, $res ) {
	}
	/// Exception handler (May be overwritten)
	public function handleException( $e, $req, $res ) {
		if ( $e instanceof \Exception )
			error_log( $e );
		
		if ( $e instanceof \LibWeb\APIException ) {
			$res->code( $e->getCode() );
			$res->data( $e );
		} else {
			$res->code( 500 );
			$res->data( $e );
		}
	}
	
};
