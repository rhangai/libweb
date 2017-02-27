<?php
/**

   Create an API

*/
class API {

	private $router;

	public function __construct() {
		$this->router = new \Klein\Klein();
	}
	/**
	 * Dispatch the current route
	 */
	public function dispatch( $base = null ) {
		$req = \Klein\Request::createFromGlobals();

		if ( $base != null ) {
			$uri = $req->server()->get( "REQUEST_URI" ) ;
			$req->server()->set( "REQUEST_URI", substr($uri, strlen($base)) );
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
		$this->router->dispatch( $req );
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
	/**
	 * Write the output
	 */
    protected function sendOutput( $output ) {
		header( 'ContentType: application/json' );
		echo json_encode( $output );
	}
	/// Cria um handler de resposta
	public function createResponseFunction( $cb, $errHandler = null ) {
		$fn = function() use ($cb, $errHandler) {
			$args = func_get_args();
			$this->response( $cb, $args, $errHandler );
		};
		return $fn;
	}
	/// Registra um objeto para resposta de API
	public function register( $obj, $root = null ) {
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
				$path = $this->nameToPath( $name, $root );
				$respondMethods = 'GET';
			} else if ( preg_match('/^POST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $this->nameToPath( $name, $root );
				$respondMethods = 'POST';
			} else if ( preg_match('/^REQUEST_(\w+)$/', $methodName, $matches ) ) {
				$name = $matches[1];
				$path = $this->nameToPath( $name, $root );
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
					$handler = $this->createResponseFunction( array( $obj, $method ), $errHandler );
                }
				$this->router->respond(
				    $respondMethods,
					$path,
					$handler
				);
			}
		}


	}

	/// Converte um nome de método para um path
	public function nameToPath( $name, $root ) {
		$path = str_replace( '_', '/', $name );
		$path = preg_replace_callback( '/([a-z])([A-Z])/', function( $matches ) {
			return $matches[1].'-'.strtolower( $matches[2] );
		}, $path );
		if ( $root )
			return '/'.$root.'/'.$path;
		return '/'.$path;
	}

};
