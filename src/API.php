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
		$this->root   = $this->normalizePath( $root );
	}
	/// Get the router
	public function getRouter() {
		return $this->router;
	}
	/// Add a new route
	private function addRoute( $methods, $path, $handler ) {
		$this->router->respond(
		    $methods,
			$this->joinPath( $this->root, $path ),
			$handler
		);
	}
	/// Register a GET method
	public function GET( $path, $handler, $errHandler = null ) {
		$handler = $this->api->createResponseFunction( $handler, $errHandler );
		$this->addRoute( "GET", $path, $handler );
	}
	/// Register a POST method
	public function POST( $path, $handler, $errHandler = null ) {
		$handler = $this->api->createResponseFunction( $handler, $errHandler );
		$this->addRoute( "GET", $path, $handler );
	}
	/// Register a POST and GET methods
	public function REQUEST( $path, $handler, $errHandler = null ) {
		$handler = $this->api->createResponseFunction( $handler, $errHandler );
		$this->addRoute( array( "GET", "POST" ), $path, $handler );
	}
	// Register the object
	public function registerObject( $obj, $base = null ) {
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
				$path = $this->api->nameToPath( $name );
				$respondMethods = 'GET';
			} else if ( preg_match('/^POST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $this->api->nameToPath( $name );
				$respondMethods = 'POST';
			} else if ( preg_match('/^REQUEST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $this->api->nameToPath( $name );
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
					$handler = $this->api->createResponseFunction( array( $obj, $method ), $errHandler );
                }
				$this->addRoute(
				    $respondMethods,
					$this->joinPath( $base, $path ),
					$handler
				);
			}
		}
	}
	// Default load function
	public static function defaultLoadFunction( $api, $file ) {
		require_once $file->path;
		$klass = $file->base . "API";
		$obj   = new $klass;
		return $obj;
	}
	// Register a dir
	public function registerDir( $root, $options = array() ) {
		$paths    = @$options["paths"];
		if ( !$paths )
			$paths = array();
		if ( !is_array( $paths ) )
			$paths = array( $paths );


		$base = @$options["base"];
		if ( !$base )
			$base = "";

		$curdir   = $root;
		$pathbase = '/';
		if ( count($paths) > 0 ) {
			$pathbase = implode( "/", $paths ) . "/";
			$curdir   = $curdir.$pathbase;
		}
		$curdir = $this->normalizePath( $curdir );

		$loadFunction = @$options["load"];
		if ( !$loadFunction ) {
			$loadFunction    = array( get_class(), 'defaultLoadFunction' );
			$options["load"] = $loadFunction;
		}
		
		$files = scandir( $curdir );
		foreach ( $files as $file ) {
			if ( ( $file === '.' ) || ( $file === '..' ) || ( $file[0] === '_' ) )
				continue;

			$filepath = $this->normalizePath( $curdir . '/'. $file );
			if ( is_dir( $filepath ) ) {
				$newpaths   = $paths;
				$newpaths[] = $file;
				$newoptions = $options;
				$newoptions["paths"] = $newpaths;
				$this->registerDir( $root, $newoptions );
				continue;
			}

			$fileext   = pathinfo($filepath, PATHINFO_EXTENSION);
			if ( $fileext !== 'php' )
				continue;
			$filebase  = pathinfo($filepath, PATHINFO_FILENAME);


			$obj = call_user_func( $loadFunction, $this, (object) array(
				"path"    => $filepath,
				"base"    => $filebase,
				"ext"     => $fileext,
				"options" => $options,
			));
		    $this->registerObject( $obj, $base . '/' . $pathbase . strtolower( $filebase ) );
		}
	}
	/**
	 * Normalize the path
	 */
    public function normalizePath( $path ) {
		if ( !$path )
			return '/';
		if ( $path[0] !== '/' )
			$path = '/'.$path;
		if ( $path[strlen($path)-1] === '/' )
			$path = substr( $path, 0, strlen($path)-1 );

		$path = preg_replace( '/\\\\/', '/', $path );
		$path = preg_replace( '/\\/\\/+/', '/', $path );
		
		return $path;
	}
	/**
	 * Join and normalize path
	 */
    public function joinPath( $path1, $path2 ) {
		if ( !$path1 )
			return $this->normalizePath( $path2 );
		else if ( !$path2 )
			return $this->normalizePath( $path1 );
		$path = $path1.'/'.$path2;
		return $this->normalizePath( $path );
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
				$data = json_decode( utf8_encode( base64_decode( $data ) ), true );
				foreach( $data as $key => $value )
					$req->paramsPost()->set( $key, $value );
			}
		}
		$this->getRouter()->dispatch( $req );
	}
	/**
	 * Call a response object and write a response
	 */
	public function response( $cb, $errHandler, $req, $res ) {
		try {
			$data = call_user_func( $cb, $req, $res );
			if ( is_object($data) && method_exists( $data, 'serializeAPI' ) )
				$data = $data->serializeAPI();
		    $this->sendOutput( $req, $res, array( "status" => "success", "data" => $data ) );
		} catch( \Exception $e ) {
			$res->code( 400 );
			error_log( $e );
			$data = null;
			if ( $errHandler ) {
				$data = call_user_func( $errHandler, $e );
				if ( is_object($data) && method_exists( $data, 'serializeAPI' ) )
					$data = $data->serializeAPI();
			} else if ( is_object( $e ) && method_exists( $e, 'serializeAPI' ) ) {
				$data = $e->serializeAPI();
			}
		    $this->sendOutput( $req, $res, array( "status" => "error", "error" => $data ) );
		}
	}
	// Wrap the response function
	public function createResponseFunction( $cb, $errHandler = null ) {
		$fn = function( $req, $res ) use ($cb, $errHandler) {
			$this->response( $cb, $errHandler, $req, $res );
		};
		return $fn;
	}
	/**
	 * Send the output
	 */
    protected function sendOutput( $req, $res, $output ) {
		$res->json( $output );
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
