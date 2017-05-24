<?php
namespace LibWeb\api;

class Request {

	private $uri_;
	private $method_;
	private $get_;
	private $post_;
	private $server_;
	private $base_;

	public function __construct( $base, $uri, $method, $get, $post, $server ) {
		$this->base_   = $base;
		$this->uri_    = parse_url( $uri, PHP_URL_PATH );
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
		return new Request( $base, $uri, $method, $_GET, $_POST, $_SERVER );
	}
	
};