<?php
use LibWeb\Validator as v;
use LibWeb\api\Request;

class ValidatorTest extends PHPUnit\Framework\TestCase
{

	/**
	 *  @dataProvider floatval_provider
	 */
	public function test_floatval( $expected, $v, $decimal = null ) {
		$this->assertEquals( $expected, v::validate( $v, v::f( $decimal ) ) );
		$this->assertEquals( $expected, v::validate( $v, v::floatval( $decimal ) ) );
	}
	public function floatval_provider() {
		return [
			[ 0, "0" ],
			[ 100, 100 ],
			[ 100, "100" ],
			[ 100.01, "100.01" ],
			[ 100.01, "100.01" ],
			[ 123123.12, "123123,12", "," ],
		];
	}
	/**
	 *  @expectedException \LibWeb\ValidatorException
	 *  @dataProvider floatval_fail_provider
	 */
	public function test_floatval_fail( $v, $decimal = null ) {
		v::validate( $v, v::f( $decimal ) );
		v::validate( $v, v::floatval( $decimal ) );
	}
	public function floatval_fail_provider() {
		return [
			[ "xupiqs" ],
			[ "MKAMSD" ],
			[ "1000.2", "," ],
		];
	}
	
	
	/**
	 *  @dataProvider intval_provider
	 */
	public function test_intval( $expected, $v ) {
		$this->assertEquals( $expected, v::validate( $v, v::i() ) );
		$this->assertEquals( $expected, v::validate( $v, v::intval() ) );
	}
	public function intval_provider() {
		return [
			[ 0, "0" ],
			[ 100, 100 ],
			[ 100, "100" ],
			[ 12300, 12300 ],
			[ 12300, "12300" ],
		];
	}
	/**
	 *  @expectedException \LibWeb\ValidatorException
	 *  @dataProvider intval_fail_provider
	 */
	public function test_intval_fail( $v ) {
		v::validate( $v, v::i() );
	}
	public function intval_fail_provider() {
		return [
			[ "xupiqs" ],
			[ "MKAMSD" ],
			[ 100.2 ],
		];
	}


    
	public function test_request() {
		$get = array(
			"name" => "test",
			"data" => "16/03",
		);
		$post = array();
		$files = array(
			"simple" => array(
				"name"     => array( "simple.txt", "data" ),
				"tmp_name" => array( "/tmp/simple.txt", "maksd" ),
				"type"     => array( "text/plain", "maksd" ),
				"error"    => array( 0, 0 ),
				"size"     => array( 0, 0 ),
			),
		);
		$server = array();
		$req = new Request( null, "", "GET", $get, $post, $files, $server );
		
		$data = $req->params(array(
			"name"    => v::s(),
			"data"    => v::date( "d/m", "Y/m/d" ),
			"simple"  => v::file(),
		));
	}

}