<?php namespace LibWeb;
/**

   Create an API configuration object

*/
class APIRouteConfiguration {

	private $api;
	private $router;
	private $root;

	/// Construct the configuration object
	public function __construct( $api, $router, $root ) {
		$this->api    = $api;
		$this->router = $router;
		$this->root   = $this->normalizePath( $root, false );
	}
	/// Get the router
	public function getRouter() {
		return $this->router;
	}
	/// Add a new route
	private function addRoute( $methods, $path, $handler ) {
		$this->router->respond(
		    $methods,
			$this->normalizePath( $path ),
			$handler
		);
	}
	/// Register a GET method
	public function GET( $path, $handler ) {
		$this->addRoute( "GET", $path, $handler );
	}
	/// Register a POST method
	public function POST( $path, $handler ) {
		$this->addRoute( "GET", $path, $handler );
	}
	/// Register a POST and GET methods
	public function REQUEST( $path, $handler ) {
		$this->addRoute( array( "GET", "POST" ), $path, $handler );
	}
	// Register the object
	public function registerObject( $obj, $root = null ) {
		$methods    = get_class_methods( get_class( $obj ) );
		$errHandler = null;
		if ( method_exists( $obj, 'handleException' ) )
			$errHandler = array( $obj, 'handleException' );

		foreach ( $methods as $method ) {
			$raw = false;
			$methodName = $method;
			if ( substr( $methodName, 0, 3 ) === 'RAW' ) {
				$raw        = true;
				$methodName = substr( $methodName, 3 );
			}

			$path           = null;
			$respondMethods = null;

			if ( preg_match('/^GET_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $api->nameToPath( $name );
				$respondMethods = 'GET';
			} else if ( preg_match('/^POST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $api->nameToPath( $name );
				$respondMethods = 'POST';
			} else if ( preg_match('/^REQUEST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $api->nameToPath( $name );
				$respondMethods = array( 'POST', 'GET' );
			}
			if ( $path ) {
				if ( $raw ) {
					$handler = function() use ($obj, $method) {
						$args = func_get_args();
					    call_user_func_array( array( $obj, $method ), $args );
						exit;
					};
				} else {
					$handler = $api->createResponseFunction( array( $obj, $method ), $errHandler );
                }
				$this->addRoute(
				    $respondMethods,
					$path,
					$handler
				);
			}
		}
	}
	/**
	 * Normalize the path
	 */
    public function normalizePath( $path, $useRoot = true ) {
		if ( $useRoot )
			$path = $this->root.'/'.$path;
		
		if ( $path[0] !== '/' )
			$path = '/'.$path;
		if ( $path[strlen($path)-1] === '/' )
			$path = substr( $path, 0, strlen($path)-1 );

		$path = preg_replace( '/\\\\/', '/', $path );
		$path = preg_replace( '/\\/\\/+/', '/', $path );
		
		return $path;
	}
	
};

/**
 * API class
 *
 */
class API extends APIRouteConfiguration {
	/**
	 *
	 */
	public function __construct( $root = '/' ) {
		$router = new \Klein\Klein();
		parent::__construct( $this, $router, $root );
	}
	/**
	 * Dispatch the current route calling the registered ones
	 */
	public function dispatch( $options = null ) {
		$req = \Klein\Request::createFromGlobals();

		// Options
		if ( $options ) {
			if ( is_string( $options ) )
				$options = array( "uri" => $options );
			   
			if ( @$options[ 'uri' ] ) {
				$req->server()->set( "REQUEST_URI", $options['uri'] );
			}
		}

		// Json
		if ( @$_SERVER['CONTENT_TYPE'] === 'application/json' ) {
			$data = $req->body();
			if ( $data != null ) {
				$data = json_decode( $data, true );
				foreach( $data as $key => $value )
					$req->paramsPost()->set( $key, $value );
			}
		} else {
			$data = $req->param( '_json' );
			if ( $data != null ) {
				$data = json_decode( base64_decode( $data ), true );
				foreach( $data as $key => $value )
					$req->paramsPost()->set( $key, $value );
			}
		}
		$this->getRouter()->dispatch( $req );
	}
	/**
	 * Call a response object and write a response
	 */
	public function response( $cb, $args = array(), $errHandler = null ) {
		try {
			$data = call_user_func_array( $cb, $args );
		    $this->sendOutput( array( "status" => "success", "data" => $data ) );
		} catch( Exception $e ) {
			http_response_code( 500 );
			error_log( $e );
			$data = null;
			if ( $errHandler )
				$data = call_user_func( $errHandler, $e );

			$this->sendOutput( array( "status" => "error", "error" => $data ) );
		}
		exit;
	}
	// Wrap the response function
	public function createResponseFunction( $cb, $errHandler = null ) {
		$fn = function() use ($cb, $errHandler) {
			$args = func_get_args();
			$this->response( $cb, $args, $errHandler );
		};
		return $fn;
	}
	/**
	 * Send the output
	 */
    protected function sendOutput( $output ) {
		header( 'ContentType: application/json' );
		echo json_encode( $output );
	}
	/**
	 * Convert a method name to a path
	 */
    public function nameToPath( $name ) {
		$path = str_replace( '_', '/', $name );
		$path = preg_replace_callback( '/([a-z])([A-Z])/', function( $matches ) {
			return $matches[1].'-'.strtolower( $matches[2] );
		}, $path );
		return '/'.$path;
	}
};
