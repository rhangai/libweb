<?php
namespace LibWeb\api;

class Request {

	private $uri_;
	private $method_;
	private $get_;
	private $post_;
	private $server_;

	public function __construct( $uri, $method, $get, $post, $server ) {
		$this->uri_    = $uri;
		$this->method_ = $method;
		$this->get_    = $get;
		$this->post_   = $post;
		$this->server_ = $server;
	}
	
	public function param( $name, $default = null ) {
		if ( isset( $this->get_[$name] ) )
			return $this->get_[$name];
		if ( isset( $this->post_[$name] ) )
			return $this->post_[$name];
		return $default;
	}
	
	public function validateParams( $rules ) {
	}

	public function uri() {
		return $this->uri_;
	}

	public function method() {
		return $this->method_;
	}
	
	public static function createFromGlobals( $uri = null, $method = null ) {
		if ( $uri === null )
			$uri = $_SERVER['REQUEST_URI'];
		if ( $method === null )
			$method = $_SERVER['REQUEST_METHOD'];
		return new Request( $uri, $method, $_GET, $_POST, $_SERVER );
	}
	
};