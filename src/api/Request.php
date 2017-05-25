<?php
namespace LibWeb\api;

class Request {

	private $base_;
	private $uri_;
	private $method_;
	private $get_;
	private $post_;
	private $files_;
	private $server_;

	private $processedFiles_;

	public function __construct( $base, $uri, $method, $get, $post, $files, $server ) {
		$this->base_   = $base;
		$this->uri_    = parse_url( $uri, PHP_URL_PATH );
		$this->method_ = $method;
		$this->get_    = $get;
		$this->post_   = $post;
		$this->server_ = $server;
		$this->files_  = $files;
	}
	
	public function param( $name, $default = null ) {
		if ( isset( $this->get_[$name] ) )
			return $this->get_[$name];
		if ( isset( $this->post_[$name] ) )
			return $this->post_[$name];
		return $default;
	}

	public function file( $name = null, $array = false ) {
		if ( $name === true ) {
			$name  = null;
			$array = true;
		}
			
		$files = $this->files();
		if ( $name === null )
			$file = reset( $files );
		else
			$file = @$files[ $name ];
		if ( !$file )
			return null;
		if ( $array )
			$file = is_array( $file ) ? $file : array( $file );
		else
			$file = is_array( $file ) ? @$file[0] : $file;
		return $file;
	}
	
	public function files() {
		if ( $this->processedFiles_ === null ) {
			$this->processedFiles_ = array();
			$errors = array();
			foreach ( $this->files_ as $name => $file ) {
				if ( is_array( @$file["tmp_name"] ) ) {
					$len     = count( $file["tmp_name"] );
					$newfile = array();
					for ( $i = 0; $i < $len; ++$i ) {
						$newfileItem = ( object ) array(
							"name"     => $file["name"][$i],
							"tmp_name" => $file["tmp_name"][$i],
							"type"     => $file["type"][$i],
							"error"    => $file["error"][$i],
							"size"     => $file["size"][$i],
							"path"     => $file["tmp_name"][$i],
						);
						self::checkFile( $errors, $name.'['.$i.']', $newfileItem );
						$newfile[] = $newfileItem;
					}
				
				} else {
					$newfile = ( object ) array(
						"name"     => $file["name"],
						"tmp_name" => $file["tmp_name"],
						"type"     => $file["type"],
						"error"    => $file["error"],
						"size"     => $file["size"],
						"path"     => $file["tmp_name"],
					);
					self::checkFile( $errors, $name, $newfile );
				}
				$this->processedFiles_[ $name ] = $newfile;
			}
			if ( $errors )
				throw new UploadException( $errors );
		}
		return $this->processedFiles_;
	}

	private static function checkFile( &$errors, $name, $file ) {
		if ( $file->error !== 0 ) {
			switch( $file->error ) {
			case UPLOAD_ERR_INI_SIZE:
				$error = "Max size exceeded";
				break;
			default:
				$error = "Error";
			};
			$errors[$name] = $error;
		}
	}
	
	public function validateParams( $rules ) {
	}

	public function uri() {
		return $this->uri_;
	}

	public function base() {
		return $this->base_;
	}

	public function method() {
		return $this->method_;
	}
	
	public static function createFromGlobals( $base = null, $uri = null, $method = null ) {
		if ( $uri === null )
			$uri = $_SERVER['REQUEST_URI'];
		if ( $method === null )
			$method = $_SERVER['REQUEST_METHOD'];
		return new Request( $base, $uri, $method, $_GET, $_POST, $_FILES, $_SERVER );
	}
	
};